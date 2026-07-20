<?php

namespace App\Http\Controllers;

use App\Support\CurrentBranch;
use App\Support\Ledger;
use App\Support\DocumentNumber;
use App\Support\Inventory;
use App\Support\TenderAccount;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Stock;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Coupon;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $branchId = CurrentBranch::id();

        $sales = Sale::with(['customer', 'user'])
            ->whereBranch($branchId)
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('invoice_no', 'like', "%{$request->search}%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
            }))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payment_method, fn($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            // Credit sales whose signed evidence photo hasn't been attached yet.
            ->when($request->filter === 'credit_no_doc', fn($q) =>
                $q->where('credit_amount', '>', 0)->whereNull('credit_doc_url'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Sales are immutable; net the revenue cards by returns recorded in the same window.
        $returnsToday = SaleReturn::whereHas('sale', fn($q) => $q->whereBranch($branchId))
            ->whereDate('created_at', today())->sum('return_amount');
        $returnsMonth = SaleReturn::whereHas('sale', fn($q) => $q->whereBranch($branchId))
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('return_amount');

        $stats = [
            'today_total'  => Sale::whereBranch($branchId)->whereDate('created_at', today())->sum('total') - $returnsToday,
            'today_count'  => Sale::whereBranch($branchId)->whereDate('created_at', today())->count(),
            'month_total'  => Sale::whereBranch($branchId)->whereMonth('created_at', now()->month)->sum('total') - $returnsMonth,
            'pending_dues' => Sale::whereBranch($branchId)->where('status', 'partial')->sum(DB::raw('total - paid_amount')),
            'credit_no_doc'=> Sale::whereBranch($branchId)->where('credit_amount', '>', 0)->whereNull('credit_doc_url')->count(),
        ];

        return view('sales.index', compact('sales', 'stats'));
    }

    public function create(Request $request)
    {
        $branchId  = CurrentBranch::id();
        $customers = Customer::orderBy('name')->get();
        $accounts  = Account::whereBranch($branchId)->get();
        $products  = Product::where('status', 'active')->orderBy('name')->get()
            ->map(function ($p) use ($branchId) {
                $p->current_stock = $p->stockForBranch($branchId);
                return $p;
            });

        // Prefill from a quotation ("Convert to sale"). The quote is only marked
        // 'converted' once this sale is actually saved (see store()).
        $prefill = null;
        if ($request->from_quote) {
            $quote = Quotation::with('items.product')
                ->whereBranch($branchId)
                ->find($request->from_quote);
            if ($quote && $quote->status !== 'converted') {
                $prefill = [
                    'quote_id'    => $quote->id,
                    'quote_no'    => $quote->quote_no,
                    'customer_id' => $quote->customer_id,
                    'items'       => $quote->items->map(fn($i) => [
                        'product_id' => $i->product_id,
                        'name'       => $i->product?->name ?? '',
                        'quantity'   => (float) $i->quantity,
                        'unit_price' => (float) $i->unit_price,
                    ])->values()->all(),
                ];
            }
        }

        return view('sales.create', compact('customers', 'products', 'accounts', 'branchId', 'prefill'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.quantity'     => 'required|numeric|min:0.001',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'payment_method'       => 'required|in:cash,card,bank_transfer,credit',
            'paid_amount'          => 'required|numeric|min:0',
        ]);

        // Line subtotal net of its own discount %; shared by the header + item rows.
        $lineSub = fn($i) => (float) $i['quantity'] * (float) $i['unit_price'] * (1 - (float) ($i['discount_pct'] ?? 0) / 100);

        if (! $branchId = CurrentBranch::requireId()) {
            return back()->withInput()->with('error', CurrentBranch::pickBranchMessage());
        }

        // Total requested per product, across however many lines it appears on.
        $needByProduct = [];
        foreach ($request->items as $item) {
            $needByProduct[$item['product_id']] = ($needByProduct[$item['product_id']] ?? 0) + (float) $item['quantity'];
        }

        DB::beginTransaction();
        try {
            // This form had no stock check at all and would happily sell into a
            // negative balance. Guarded inside the transaction, as at the till.
            if ($shortage = Inventory::guard($needByProduct, $branchId)) {
                DB::rollBack();
                return back()->withInput()->with('error', $shortage);
            }

            $subtotal = collect($request->items)->sum($lineSub);
            $tax      = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_price'] * ($i['tax_percent'] ?? 0) / 100);
            $discount = (float) ($request->discount_amount ?? 0);
            $total    = $subtotal + $tax - $discount;
            $paid     = min((float) $request->paid_amount, $total);

            // Credit guard (mirror of the POS rule): an on-account balance is only for
            // registered, approved customers (or any registered customer when the store
            // allows credit for new ones), never walk-in, and within their credit limit.
            $creditRemainder = round($total - $paid, 2);
            if ($request->payment_method === 'credit' && $creditRemainder > 0) {
                $creditCustomer = $request->customer_id ? Customer::find($request->customer_id) : null;
                $allowNew = filter_var(\App\Models\Setting::get('allow_credit_new_customers'), FILTER_VALIDATE_BOOLEAN);
                $err = null;
                if (! $creditCustomer) {
                    $err = "Select a registered customer for credit — walk-in customers can't buy on credit.";
                } elseif (! $creditCustomer->credit_approved && ! $allowNew) {
                    $err = 'This customer is not approved for credit.';
                } elseif (blank($creditCustomer->nic)) {
                    $err = "Add the customer's NIC before selling on credit.";
                } elseif ($creditCustomer->credit_limit !== null
                        && ($creditCustomer->outstandingBalance() + $creditRemainder) > (float) $creditCustomer->credit_limit + 1e-9) {
                    $over = round($creditCustomer->outstandingBalance() + $creditRemainder - (float) $creditCustomer->credit_limit, 2);
                    $err = "Over the customer's credit limit by Rs. " . number_format($over, 2) . '.';
                }
                if ($err) {
                    DB::rollBack();
                    return back()->withInput()->with('error', $err);
                }
            }

            $sale = Sale::create([
                'invoice_no'      => DocumentNumber::next('invoice'),
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
                'credit_amount'   => $request->payment_method === 'credit' ? $creditRemainder : 0,
                'payment_method'  => $request->payment_method,
                'status'          => $paid >= $total ? 'paid' : 'partial',
                'notes'           => $request->input('note', $request->notes),
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $cogs    = $product
                    ? Inventory::consume($product, $branchId, (float) $item['quantity'], (float) $item['unit_price'])
                    : 0;

                SaleItem::create([
                    'sale_id'          => $sale->id,
                    'product_id'       => $item['product_id'],
                    'quantity'         => $item['quantity'],
                    'unit_price'       => $item['unit_price'],
                    'cost'             => $cogs,
                    'discount_percent' => (float) ($item['discount_pct'] ?? 0),
                    'tax_percent'      => $item['tax_percent'] ?? 0,
                    'subtotal'         => $lineSub($item),
                ]);
            }

            // Loyalty points
            if ($request->customer_id) {
                Customer::find($request->customer_id)->increment('loyalty_points', (int)($total / 20));
                Customer::find($request->customer_id)->increment('total_purchases', $total);
            }

            // Payment record — honour the chosen account, and map the sale's payment
            // method to a valid payments.method ENUM value ('cash','card','bank','cheque').
            if ($paid > 0) {
                $methodMap = ['cash' => 'cash', 'card' => 'card', 'bank_transfer' => 'bank', 'credit' => 'cash'];
                $tender    = $methodMap[$request->payment_method] ?? 'cash';

                // An explicitly chosen account wins; otherwise fall to whichever
                // account that tender belongs in rather than always the cash one.
                $account = ($request->account_id ? Account::whereBranch($branchId)->find($request->account_id) : null)
                        ?? TenderAccount::for($branchId, $tender);
                if ($account) {
                    $reference = 'PAY-' . strtoupper(Str::random(8));

                    Payment::create([
                        'reference_no' => $reference,
                        'type'         => 'payment_in',
                        'account_id'   => $account->id,
                        'party_type'   => 'customer',
                        'party_id'     => $request->customer_id,
                        'sale_id'      => $sale->id,
                        'amount'       => $paid,
                        'method'       => $tender,
                        'created_by'   => auth()->id(),
                    ]);
                    Ledger::credit($account, $paid, [
                        'reference'   => $reference,
                        'description' => "Sale {$sale->invoice_no}",
                        'source_type' => 'sale',
                        'source_id'   => $sale->id,
                    ]);
                }
            }

            // If this sale came from a quotation, mark it converted now (not before).
            if ($request->from_quote) {
                Quotation::where('id', $request->from_quote)
                    ->whereBranch($branchId)
                    ->where('status', '!=', 'converted')
                    ->update(['status' => 'converted']);
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
        CurrentBranch::guard($sale->branch_id);
        $sale->load(['items.product', 'customer', 'branch', 'user', 'payments']);
        return view('sales.show', compact('sale'));
    }

    public function destroy(Sale $sale)
    {
        if (! CurrentBranch::allows($sale->branch_id)) {
            return back()->with('error', 'Sale not found for this branch.');
        }
        // Returns already re-added stock / refunded separately; reverse those first.
        if ($sale->returns()->exists()) {
            return back()->with('error', 'This sale has returns recorded against it — reverse those first.');
        }

        DB::beginTransaction();
        try {
            $branchId = $sale->branch_id;
            $sale->load('items.product', 'payments');

            // Put the sold stock back as a fresh layer at the captured cost + sold price
            // (keeps the aggregate == Σ layer qty_remaining invariant intact).
            foreach ($sale->items as $item) {
                if (! $item->product_id) {
                    continue;   // custom / one-off line — no stock to restore
                }
                $qty         = (float) $item->quantity;
                $perUnitCost = ($item->cost !== null && $qty > 0)
                    ? (float) $item->cost / $qty
                    : (float) ($item->product?->purchase_price ?? 0);
                Inventory::addLayer(
                    $item->product_id, $branchId, $qty,
                    $perUnitCost, (float) $item->unit_price,
                    'VOID', now()->toDateString()
                );
            }

            // Refund every recorded payment back to its account, then drop it.
            foreach ($sale->payments as $payment) {
                Ledger::debit($payment->account_id, (float) $payment->amount, [
                    'description' => "Reversed — sale {$sale->invoice_no} deleted",
                    'source_type' => 'sale',
                    'source_id'   => $sale->id,
                ]);
                $payment->delete();
            }

            // Undo loyalty points + the customer's running purchase total.
            if ($sale->customer_id) {
                $pts = (int) ($sale->total / 20);
                if ($pts > 0) {
                    Customer::where('id', $sale->customer_id)->decrement('loyalty_points', $pts);
                }
                Customer::where('id', $sale->customer_id)->decrement('total_purchases', $sale->total);
            }

            $invoiceNo = $sale->invoice_no;
            $sale->items()->delete();
            $sale->delete();

            DB::commit();
            return redirect()->route('sales.index')->with('success', "Sale {$invoiceNo} voided — stock and payments reversed.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Void failed: ' . $e->getMessage());
        }
    }

    // PDF Invoice
    public function invoice(int $id)
    {
        $sale     = Sale::with(['items.product', 'customer', 'branch', 'user'])
            ->whereBranch(CurrentBranch::id())->findOrFail($id);
        $settings = \App\Models\Setting::pluck('value', 'key_name');

        $pdf = Pdf::loadView('sales.invoice_pdf', compact('sale', 'settings'))
                  ->setPaper('A5', 'portrait');

        return $pdf->download("Invoice-{$sale->invoice_no}.pdf");
    }

    // Thermal receipt view
    public function receipt(int $id)
    {
        $sale     = Sale::with(['items.product', 'customer', 'branch'])
            ->whereBranch(CurrentBranch::id())->findOrFail($id);
        $settings = \App\Models\Setting::pluck('value', 'key_name');

        return view('sales.receipt', compact('sale', 'settings'));
    }

}
