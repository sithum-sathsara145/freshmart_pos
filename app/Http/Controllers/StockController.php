<?php

namespace App\Http\Controllers;

use App\Support\CurrentBranch;

use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\StockConversion;
use App\Models\ProductConversion;
use App\Models\Product;
use App\Models\Branch;
use App\Support\BulkBreak;
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

    // ── Breaking bulk down into retail ────────────────────────────────────

    public function conversions(Request $request)
    {
        $branchId = CurrentBranch::id();

        $rules = ProductConversion::with(['from', 'to'])
            ->where('status', 'active')
            ->get()
            ->sortBy(fn ($r) => $r->from?->name)
            ->values();

        // How much bulk is on hand, so the screen can say what's breakable here.
        $onHand = Stock::whereBranch($branchId)
            ->whereIn('product_id', $rules->pluck('from_product_id'))
            ->pluck('quantity', 'product_id');

        $retailOnHand = Stock::whereBranch($branchId)
            ->whereIn('product_id', $rules->pluck('to_product_id'))
            ->pluck('quantity', 'product_id');

        $history = StockConversion::with(['from', 'to', 'createdBy'])
            ->whereBranch($branchId)
            ->latest()
            ->paginate(15);

        // No product list is passed: the two pickers search /api/products/search
        // instead. A shop this size has thousands of products, and rendering them
        // twice as <option>s made the page heavy and the dropdowns unusable.
        return view('stock.conversions', compact('rules', 'onHand', 'retailOnHand', 'history'));
    }

    public function storeConversion(Request $request)
    {
        $request->validate([
            'conversion_id' => 'required|exists:product_conversions,id',
            'from_qty'      => 'required|numeric|min:0.001',
            'to_qty'        => 'nullable|numeric|min:0.001',
            'note'          => 'nullable|string|max:255',
        ]);

        if (! $branchId = CurrentBranch::requireId()) {
            return back()->withInput()->with('error', CurrentBranch::pickBranchMessage());
        }

        $rule = ProductConversion::with(['from', 'to'])->findOrFail($request->conversion_id);

        // Weight doesn't go missing when a bag is tipped into a bin, so those
        // yields are fixed. Counted packets are where spillage happens, so there
        // the person doing it says what really came out.
        $expected = BulkBreak::expectedYield($rule, (float) $request->from_qty);
        $produced = $rule->yieldIsFixed()
            ? $expected
            : (float) ($request->to_qty ?? $expected);

        if ($reason = BulkBreak::refusalReason($rule, $branchId, (float) $request->from_qty, $produced)) {
            return back()->withInput()->with('error', $reason);
        }

        $conversion = BulkBreak::run($rule, $branchId, (float) $request->from_qty, $produced, $request->note);

        $trim = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
        $msg  = "Broke {$trim($conversion->from_qty)} × {$rule->from->name} into "
              . "{$trim($conversion->to_qty)} {$rule->to->unit} of {$rule->to->name}.";

        if ($conversion->hadWastage()) {
            $msg .= " {$trim($conversion->wastage_qty)} {$rule->to->unit} short of the usual yield.";
        }

        return back()->with('success', $msg);
    }

    public function storeConversionRule(Request $request)
    {
        $request->validate([
            'from_product_id' => 'required|exists:products,id|different:to_product_id',
            'to_product_id'   => 'required|exists:products,id',
            'yield_qty'       => 'required|numeric|min:0.001',
        ], [
            'from_product_id.different' => 'A product cannot be broken into itself.',
        ]);

        ProductConversion::updateOrCreate(
            [
                'from_product_id' => $request->from_product_id,
                'to_product_id'   => $request->to_product_id,
            ],
            [
                'yield_qty'  => $request->yield_qty,
                'status'     => 'active',
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Breakdown saved.');
    }

    public function destroyConversionRule(ProductConversion $conversion)
    {
        $conversion->delete();

        return back()->with('success', 'Breakdown removed.');
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
