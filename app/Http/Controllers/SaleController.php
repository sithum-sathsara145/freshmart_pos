<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Stock;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $sales = Sale::with(['customer', 'user'])
            ->where('branch_id', $branchId)
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('invoice_no', 'like', "%{$request->search}%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
            }))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payment_method, fn($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'today_total'  => Sale::where('branch_id', $branchId)->whereDate('created_at', today())->sum('total'),
            'today_count'  => Sale::where('branch_id', $branchId)->whereDate('created_at', today())->count(),
            'month_total'  => Sale::where('branch_id', $branchId)->whereMonth('created_at', now()->month)->sum('total'),
            'pending_dues' => Sale::where('branch_id', $branchId)->where('status', 'partial')->sum(DB::raw('total - paid_amount')),
        ];

        return view('sales.index', compact('sales', 'stats'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $branchId  = auth()->user()->branch_id;

        return view('sales.create', compact('customers', 'branchId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_method'     => 'required|in:cash,card,credit,mixed',
            'paid_amount'        => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $branchId = auth()->user()->branch_id;

            $subtotal = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $tax      = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_price'] * ($i['tax_percent'] ?? 0) / 100);
            $discount = (float) ($request->discount_amount ?? 0);
            $total    = $subtotal + $tax - $discount;
            $paid     = min((float) $request->paid_amount, $total);

            $sale = Sale::create([
                'invoice_no'      => $this->nextInvoiceNo(),
                'customer_id'     => $request->customer_id,
                'branch_id'       => $branchId,
                'counter_id'      => auth()->user()->counter_id,
                'user_id'         => auth()->id(),
                'coupon_id'       => $request->coupon_id,
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => $tax,
                'total'           => $total,
                'paid_amount'     => $paid,
                'change_amount'   => max(0, (float) $request->paid_amount - $total),
                'payment_method'  => $request->payment_method,
                'status'          => $paid >= $total ? 'paid' : 'partial',
                'notes'           => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $cogs    = $product
                    ? \App\Support\Inventory::consume($product, $branchId, (float) $item['quantity'], (float) $item['unit_price'])
                    : 0;

                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $item['product_id'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'cost'         => $cogs,
                    'tax_percent'  => $item['tax_percent'] ?? 0,
                    'subtotal'     => $item['quantity'] * $item['unit_price'],
                ]);
            }

            // Loyalty points
            if ($request->customer_id) {
                Customer::find($request->customer_id)->increment('loyalty_points', (int)($total / 20));
                Customer::find($request->customer_id)->increment('total_purchases', $total);
            }

            // Payment record
            if ($paid > 0) {
                $account = Account::where('branch_id', $branchId)->where('type', 'cash')->first();
                if ($account) {
                    Payment::create([
                        'reference_no' => 'PAY-' . strtoupper(Str::random(8)),
                        'type'         => 'payment_in',
                        'account_id'   => $account->id,
                        'party_type'   => 'customer',
                        'party_id'     => $request->customer_id,
                        'sale_id'      => $sale->id,
                        'amount'       => $paid,
                        'method'       => $request->payment_method,
                        'created_by'   => auth()->id(),
                    ]);
                    $account->increment('balance', $paid);
                }
            }

            DB::commit();
            return redirect()->route('sales.show', $sale)->with('success', "Invoice #{$sale->invoice_no} created.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Sale failed: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'customer', 'branch', 'user', 'payments']);
        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        // Only allow editing pending/partial sales
        if ($sale->status === 'returned') {
            return back()->with('error', 'Cannot edit a returned sale.');
        }
        $customers = Customer::orderBy('name')->get();
        $sale->load(['items.product']);
        return view('sales.edit', compact('sale', 'customers'));
    }

    public function update(Request $request, Sale $sale)
    {
        // Only allow updating notes and payment for now
        $sale->update([
            'notes'       => $request->notes,
            'paid_amount' => min((float)$request->paid_amount, $sale->total),
            'status'      => (float)$request->paid_amount >= $sale->total ? 'paid' : 'partial',
        ]);

        return redirect()->route('sales.show', $sale)->with('success', 'Sale updated.');
    }

    public function destroy(Sale $sale)
    {
        if ($sale->status === 'paid') {
            return back()->with('error', 'Cannot delete a paid sale.');
        }
        $sale->delete();
        return redirect()->route('sales.index')->with('success', 'Sale deleted.');
    }

    // PDF Invoice
    public function invoice(int $id)
    {
        $sale     = Sale::with(['items.product', 'customer', 'branch', 'user'])->findOrFail($id);
        $settings = \App\Models\Setting::pluck('value', 'key_name');

        $pdf = Pdf::loadView('sales.invoice_pdf', compact('sale', 'settings'))
                  ->setPaper('A5', 'portrait');

        return $pdf->download("Invoice-{$sale->invoice_no}.pdf");
    }

    // Thermal receipt view
    public function receipt(int $id)
    {
        $sale     = Sale::with(['items.product', 'customer', 'branch'])->findOrFail($id);
        $settings = \App\Models\Setting::pluck('value', 'key_name');

        return view('sales.receipt', compact('sale', 'settings'));
    }

    private function nextInvoiceNo(): string
    {
        $last = Sale::latest('id')->value('invoice_no');
        $num  = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;
        return 'INV-' . str_pad($num, 6, '0', STR_PAD_LEFT);
    }
}
