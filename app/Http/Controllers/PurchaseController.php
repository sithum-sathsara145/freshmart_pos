<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLayer;
use App\Models\Payment;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $purchases = Purchase::with(['supplier', 'user'])
            ->where('branch_id', $branchId)
            ->when($request->search, fn($q) => $q->where('bill_no', 'like', "%{$request->search}%")
                ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$request->search}%")))
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->from_date, fn($q) => $q->whereDate('purchase_date', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('purchase_date', '<=', $request->to_date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'month_total'    => Purchase::where('branch_id', $branchId)->whereMonth('created_at', now()->month)->sum('total'),
            'month_count'    => Purchase::where('branch_id', $branchId)->whereMonth('created_at', now()->month)->count(),
            'balance_due'    => Purchase::where('branch_id', $branchId)->where('payment_status', '!=', 'paid')->sum('balance_due'),
            'paid_this_month'=> Purchase::where('branch_id', $branchId)->whereMonth('created_at', now()->month)->sum('paid_amount'),
        ];

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.index', compact('purchases', 'stats', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('purchases.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'        => 'required|exists:suppliers,id',
            'purchase_date'      => 'required|date',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_no'   => 'nullable|string|max:50',
            'items.*.mrp'        => 'nullable|numeric|min:0',
            'items.*.sale_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $branchId = auth()->user()->branch_id;
            $subtotal = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $discount = (float) ($request->discount_amount ?? 0);
            $tax      = (float) ($request->tax_amount ?? 0);
            $total    = $subtotal - $discount + $tax;
            $paid     = (float) ($request->paid_amount ?? 0);

            $purchase = Purchase::create([
                'bill_no'         => $this->nextBillNo(),
                'supplier_id'     => $request->supplier_id,
                'branch_id'       => $branchId,
                'user_id'         => auth()->id(),
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => $tax,
                'total'           => $total,
                'paid_amount'     => $paid,
                'balance_due'     => max(0, $total - $paid),
                'payment_status'  => $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                'purchase_date'   => $request->purchase_date,
                'due_date'        => $request->due_date,
                'notes'           => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $qty     = (float) $item['quantity'];
                $cost    = (float) $item['unit_price'];
                $batch   = $item['batch_no'] ?? null;
                $mrp         = isset($item['mrp']) && $item['mrp'] !== '' ? (float) $item['mrp'] : null;
                $enteredSale = isset($item['sale_price']) && $item['sale_price'] !== '' ? (float) $item['sale_price'] : null;

                // On-hand before this purchase (aggregate).
                $onHand = (float) (Stock::where('product_id', $product->id)->where('branch_id', $branchId)->value('quantity') ?? 0);

                if ($product->is_weighed) {
                    // Weighted-average cost; the sale price is set manually (defaults to current).
                    $newQty = $onHand + $qty;
                    $wac = $newQty > 0
                        ? (($onHand * (float) $product->purchase_price) + ($qty * $cost)) / $newQty
                        : $cost;
                    $product->purchase_price = round($wac, 2);
                    if ($enteredSale !== null) $product->sale_price = $enteredSale;
                    if ($mrp !== null)         $product->mrp = $mrp;
                    $product->save();

                    $layerCost = round($wac, 2);                 // weighed COGS uses WAC
                    $layerSale = (float) $product->sale_price;   // weighed is single-price
                } else {
                    // Non-weighed: the layer keeps its own cost + sale price. FIFO (same price,
                    // varying cost) and multi-price (varying sale price) both fall out of this.
                    $layerSale = $enteredSale ?? (float) $product->sale_price;
                    $layerCost = $cost;
                    if ($mrp !== null)         $product->mrp = $mrp;
                    if ($enteredSale !== null) $product->sale_price = $enteredSale;   // default = latest
                    $product->purchase_price = $cost;                                // display latest cost
                    $product->save();
                }

                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $product->id,
                    'quantity'    => $qty,
                    'unit_price'  => $cost,
                    'batch_no'    => $batch,
                    'mrp'         => $mrp,
                    'sale_price'  => $layerSale,
                    'subtotal'    => $qty * $cost,
                ]);

                // FIFO / batch cost layer + aggregate on-hand (kept in sync centrally).
                \App\Support\Inventory::addLayer(
                    $product->id, $branchId, $qty, $layerCost, $layerSale,
                    $batch, $request->purchase_date, $purchaseItem->id
                );
            }

            // Update supplier balance
            Supplier::find($request->supplier_id)->increment('total_purchases', $total);
            if ($paid < $total) {
                Supplier::find($request->supplier_id)->increment('balance_due', $total - $paid);
            }

            // Payment record
            if ($paid > 0) {
                $account = Account::where('branch_id', $branchId)->first();
                if ($account) {
                    Payment::create([
                        'reference_no' => 'PAY-' . strtoupper(Str::random(8)),
                        'type'         => 'payment_out',
                        'account_id'   => $account->id,
                        'party_type'   => 'supplier',
                        'party_id'     => $request->supplier_id,
                        'purchase_id'  => $purchase->id,
                        'amount'       => $paid,
                        'method'       => $request->payment_method ?? 'cash',
                        'created_by'   => auth()->id(),
                    ]);
                    $account->decrement('balance', $paid);
                }
            }

            DB::commit();
            return redirect()->route('purchases.index')->with('success', "Purchase #{$purchase->bill_no} saved.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Purchase failed: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['items.product', 'supplier', 'branch', 'user']);
        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $purchase->load(['items.product']);
        return view('purchases.edit', compact('purchase', 'suppliers'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $purchase->update(['notes' => $request->notes]);
        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase updated.');
    }

    public function destroy(Purchase $purchase)
    {
        if ($purchase->payment_status === 'paid') {
            return back()->with('error', 'Cannot delete a paid purchase.');
        }
        $purchase->delete();
        return redirect()->route('purchases.index')->with('success', 'Purchase deleted.');
    }

    public function bill(int $id)
    {
        $purchase = Purchase::with(['items.product', 'supplier', 'branch'])->findOrFail($id);
        $settings = \App\Models\Setting::pluck('value', 'key_name');
        $pdf      = Pdf::loadView('purchases.bill_pdf', compact('purchase', 'settings'))->setPaper('A4');
        return $pdf->download("Bill-{$purchase->bill_no}.pdf");
    }

    private function nextBillNo(): string
    {
        $last = Purchase::latest('id')->value('bill_no');
        $num  = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;
        return 'PO-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
