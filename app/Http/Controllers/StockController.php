<?php

namespace App\Http\Controllers;

use App\Support\CurrentBranch;

use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Product;
use App\Models\Branch;
use App\Support\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $branchId = CurrentBranch::id();

        $stocks = Stock::with(['product.category', 'product.brand'])
            ->whereBranch($branchId)
            ->when($request->category_id, fn($q) => $q->whereHas('product', fn($q) => $q->where('category_id', $request->category_id)))
            ->when($request->search, fn($q) => $q->whereHas('product', fn($q) => $q->where('name', 'like', "%{$request->search}%")))
            ->paginate(20);

        $totals = [
            'products'    => Stock::whereBranch($branchId)->count(),
            'total_value' => Stock::whereBranch($branchId)
                ->join('products', 'stock.product_id', '=', 'products.id')
                ->sum(DB::raw('stock.quantity * products.purchase_price')),
            'low'  => Stock::whereBranch($branchId)->whereRaw('quantity < (SELECT min_stock FROM products WHERE id = stock.product_id)')->where('quantity', '>', 0)->count(),
            'out'  => Stock::whereBranch($branchId)->where('quantity', '<=', 0)->count(),
        ];

        return view('stock.index', compact('stocks', 'totals'));
    }

    public function adjustments(Request $request)
    {
        $adjustments = StockAdjustment::with(['product', 'createdBy'])
            ->whereBranch(CurrentBranch::id())
            ->latest()->paginate(20);

        $products = Product::orderBy('name')->get(['id', 'name', 'sku']);

        return view('stock.adjustments', compact('adjustments', 'products'));
    }

    public function storeAdjustment(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:add,remove,damage,expired,set',
            'quantity'   => 'required|numeric|min:0.001',
            'reason'     => 'nullable|string',
        ]);

        if (! $branchId = CurrentBranch::requireId()) {
            return back()->withInput()->with('error', CurrentBranch::pickBranchMessage());
        }

        DB::beginTransaction();
        try {
            $product  = Product::findOrFail($request->product_id);
            $onHand   = (float) (Stock::where('product_id', $product->id)->whereBranch($branchId)->value('quantity') ?? 0);

            // The amount actually applied — removals are capped at what's on hand.
            $requested = (float) $request->quantity;
            $applied = in_array($request->type, ['add', 'set'], true) ? $requested : min($requested, $onHand);

            // Log the real change, not just the requested amount.
            StockAdjustment::create([
                'product_id' => $product->id,
                'branch_id'  => $branchId,
                'type'       => $request->type,
                'quantity'   => $applied,
                'reason'     => $request->reason,
                'created_by' => auth()->id(),
            ]);

            // Route through Inventory so the cost layers stay in sync with the aggregate.
            $today = now()->toDateString();
            if ($request->type === 'add') {
                Inventory::addLayer($product->id, $branchId, $applied, (float) $product->purchase_price, (float) $product->sale_price, 'ADJUST', $today);
            } elseif ($request->type === 'set') {
                $diff = $requested - $onHand;
                if ($diff > 0) {
                    Inventory::addLayer($product->id, $branchId, $diff, (float) $product->purchase_price, (float) $product->sale_price, 'ADJUST', $today);
                } elseif ($diff < 0) {
                    Inventory::consume($product, $branchId, -$diff);
                }
            } else {   // remove / damage / expired
                Inventory::consume($product, $branchId, $applied);
            }

            DB::commit();
            return back()->with('success', 'Stock adjusted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function transfers(Request $request)
    {
        // A transfer is "mine" if either leg touches my branch. In All-branches mode
        // there's no working branch to compare against, so every transfer is listed.
        $transfers = StockTransfer::with(['product', 'fromBranch', 'toBranch'])
            ->when(CurrentBranch::id(), fn($q, $branchId) => $q->where(
                fn($w) => $w->where('from_branch_id', $branchId)->orWhere('to_branch_id', $branchId)
            ))
            ->latest()->paginate(20);

        $branches = Branch::where('status', 'active')->get();
        $products = Product::orderBy('name')->get(['id', 'name', 'sku']);

        return view('stock.transfers', compact('transfers', 'branches', 'products'));
    }

    public function storeTransfer(Request $request)
    {
        $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id'   => 'required|exists:branches,id|different:from_branch_id',
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|numeric|min:0.001',
        ]);

        // The source branch comes from the form, so it is untrusted: a scoped user
        // must not move stock out of a branch they don't work in.
        if (! CurrentBranch::allows((int) $request->from_branch_id)) {
            return back()->withInput()->with('error', 'You cannot transfer stock out of that branch.');
        }

        // Check source stock
        $sourceStock = Stock::where('product_id', $request->product_id)
                            ->where('branch_id', $request->from_branch_id)
                            ->first();

        if (!$sourceStock || $sourceStock->quantity < $request->quantity) {
            return back()->with('error', 'Insufficient stock in source branch.');
        }

        DB::beginTransaction();
        try {
            $transfer = StockTransfer::create([
                ...$request->only(['from_branch_id', 'to_branch_id', 'product_id', 'quantity', 'notes']),
                'status'     => 'completed',
                'created_by' => auth()->id(),
            ]);

            // Move cost layers from source to destination (FIFO out, single layer in).
            $product  = Product::findOrFail($request->product_id);
            $qty      = (float) $request->quantity;
            $cogs     = Inventory::consume($product, (int) $request->from_branch_id, $qty);
            $unitCost = $qty > 0 ? round($cogs / $qty, 2) : (float) $product->purchase_price;
            Inventory::addLayer($product->id, (int) $request->to_branch_id, $qty, $unitCost, (float) $product->sale_price, 'TRANSFER', now()->toDateString());

            DB::commit();
            return back()->with('success', 'Stock transferred successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateTransferStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:pending,in_transit,completed']);
        StockTransfer::findOrFail($id)->update(['status' => $request->status]);
        return back()->with('success', 'Transfer status updated.');
    }

    public function apiGetStock(int $productId, int $branchId)
    {
        // The branch comes straight from the URL, so it is untrusted: a scoped user
        // must not read another branch's stock by editing the id.
        CurrentBranch::guard($branchId);

        $stock = Stock::where('product_id', $productId)->whereBranch($branchId)->value('quantity') ?? 0;
        return response()->json(['stock' => $stock]);
    }
}
