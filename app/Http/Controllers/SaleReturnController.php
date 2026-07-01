<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Stock;
use App\Models\Product;
use App\Support\Inventory;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $returns = SaleReturn::with(['sale', 'customer'])
            ->whereHas('sale', fn($q) => $q->where('branch_id', $branchId))
            ->when($request->search, fn($q) => $q->where('credit_note_no', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20);

        $stats = [
            'total_returns'  => SaleReturn::whereHas('sale', fn($q) => $q->where('branch_id', $branchId))->count(),
            'total_amount'   => SaleReturn::whereHas('sale', fn($q) => $q->where('branch_id', $branchId))->sum('return_amount'),
            'this_month'     => SaleReturn::whereHas('sale', fn($q) => $q->where('branch_id', $branchId))->whereMonth('created_at', now()->month)->sum('return_amount'),
        ];

        return view('sale-returns.index', compact('returns', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_id'         => 'required|exists:sales,id',
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'reason'          => 'required|string',
            'refund_method'   => 'required|in:cash,credit_note,exchange',
        ]);

        DB::beginTransaction();
        try {
            $sale         = Sale::findOrFail($request->sale_id);
            $returnAmount = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);

            $saleReturn = SaleReturn::create([
                'credit_note_no' => $this->nextCreditNoteNo(),
                'sale_id'        => $sale->id,
                'customer_id'    => $sale->customer_id,
                'reason'         => $request->reason,
                'return_amount'  => $returnAmount,
                'refund_method'  => $request->refund_method,
                'created_by'     => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'product_id'     => $item['product_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'subtotal'       => $item['quantity'] * $item['unit_price'],
                ]);

                // Return stock as a fresh layer (re-sellable at the returned price).
                $product = Product::find($item['product_id']);
                if ($product) {
                    Inventory::addLayer(
                        $product->id, $sale->branch_id, (float) $item['quantity'],
                        (float) $product->purchase_price, (float) $item['unit_price'],
                        'RETURN', now()->toDateString()
                    );
                }
            }

            // Update sale status
            $newPaid = max(0, $sale->paid_amount - $returnAmount);
            $sale->update([
                'paid_amount' => $newPaid,
                'status'      => 'returned',
            ]);

            // Deduct loyalty points
            if ($sale->customer_id) {
                $pts = (int)($returnAmount / 20);
                Customer::find($sale->customer_id)->decrement('loyalty_points', $pts);
            }

            DB::commit();
            return redirect()->route('sale-returns.index')->with('success', "Credit Note #{$saleReturn->credit_note_no} issued.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Return failed: ' . $e->getMessage())->withInput();
        }
    }

    public function create()
    {
        $sales = Sale::with('customer')->where('status', '!=', 'returned')->latest()->limit(50)->get();
        return view('sale-returns.create', compact('sales'));
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale.items.product', 'customer']);
        return view('sale-returns.show', compact('saleReturn'));
    }

    public function edit(SaleReturn $saleReturn) { return view('sale-returns.edit', compact('saleReturn')); }
    public function update(Request $request, SaleReturn $saleReturn) { return back(); }
    public function destroy(SaleReturn $saleReturn) { return back(); }

    private function nextCreditNoteNo(): string
    {
        $last = SaleReturn::latest('id')->value('credit_note_no');
        $num  = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;
        return 'CR-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
