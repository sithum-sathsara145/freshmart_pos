<?php

namespace App\Http\Controllers;

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
        $branchId = auth()->user()->branch_id;

        $stocks = Stock::with(['product.category', 'product.brand'])
            ->where('branch_id', $branchId)
            ->when($request->category_id, fn($q) => $q->whereHas('product', fn($q) => $q->where('category_id', $request->category_id)))
            ->when($request->search, fn($q) => $q->whereHas('product', fn($q) => $q->where('name', 'like', "%{$request->search}%")))
            ->paginate(20);

        $totals = [
            'products'    => Stock::where('branch_id', $branchId)->count(),
            'total_value' => Stock::where('branch_id', $branchId)
                ->join('products', 'stock.product_id', '=', 'products.id')
                ->sum(DB::raw('stock.quantity * products.purchase_price')),
            'low'  => Stock::where('branch_id', $branchId)->whereRaw('quantity < (SELECT min_stock FROM products WHERE id = stock.product_id)')->where('quantity', '>', 0)->count(),
            'out'  => Stock::where('branch_id', $branchId)->where('quantity', '<=', 0)->count(),
        ];

        return view('stock.index', compact('stocks', 'totals'));
    }

    public function adjustments(Request $request)
    {
        $adjustments = StockAdjustment::with(['product', 'createdBy'])
            ->where('branch_id', auth()->user()->branch_id)
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

        DB::beginTransaction();
        try {
            $branchId = auth()->user()->branch_id;
            $product  = Product::findOrFail($request->product_id);
            $onHand   = (float) (Stock::where('product_id', $product->id)->where('branch_id', $branchId)->value('quantity') ?? 0);

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
        $transfers = StockTransfer::with(['product', 'fromBranch', 'toBranch'])
            ->where(fn($q) => $q->where('from_branch_id', auth()->user()->branch_id)
                ->orWhere('to_branch_id', auth()->user()->branch_id))
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
        $stock = Stock::where('product_id', $productId)->where('branch_id', $branchId)->value('quantity') ?? 0;
        return response()->json(['stock' => $stock]);
    }
}
