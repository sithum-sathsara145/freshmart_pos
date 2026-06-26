<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Product;
use App\Models\Branch;
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
        $adjustments = StockAdjustment::with(['product', 'branch'])
            ->where('branch_id', auth()->user()->branch_id)
            ->latest()->paginate(20);

        return view('stock.adjustments', compact('adjustments'));
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

            StockAdjustment::create([
                ...$request->only(['product_id', 'type', 'quantity', 'reason']),
                'branch_id'  => $branchId,
                'created_by' => auth()->id(),
            ]);

            $stock = Stock::firstOrCreate(
                ['product_id' => $request->product_id, 'branch_id' => $branchId],
                ['quantity'   => 0]
            );

            match($request->type) {
                'add'     => $stock->increment('quantity', $request->quantity),
                'remove',
                'damage',
                'expired' => $stock->decrement('quantity', min($request->quantity, $stock->quantity)),
                'set'     => $stock->update(['quantity' => $request->quantity]),
            };

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

        return view('stock.transfers', compact('transfers', 'branches'));
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

            // Deduct from source
            $sourceStock->decrement('quantity', $request->quantity);

            // Add to destination
            Stock::updateOrCreate(
                ['product_id' => $request->product_id, 'branch_id' => $request->to_branch_id],
                ['quantity'   => DB::raw("quantity + {$request->quantity}")]
            );

            DB::commit();
            return back()->with('success', 'Stock transferred successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateTransferStatus(int $id)
    {
        StockTransfer::findOrFail($id)->update(['status' => request('status')]);
        return back()->with('success', 'Transfer status updated.');
    }

    public function apiGetStock(int $productId, int $branchId)
    {
        $stock = Stock::where('product_id', $productId)->where('branch_id', $branchId)->value('quantity') ?? 0;
        return response()->json(['stock' => $stock]);
    }
}
