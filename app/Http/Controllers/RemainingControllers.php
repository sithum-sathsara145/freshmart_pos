<?php

namespace App\Http\Controllers;


use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PurchaseReturn;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Stock;
use App\Models\OnlineOrder;
use App\Models\OnlineOrderItem;
use App\Models\Setting;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Picqer\Barcode\BarcodeGeneratorPNG;


// =========================================================
// Customer Controller
// =========================================================
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%"))
            ->orderByDesc('total_purchases')
            ->paginate(20);

        return view('customers.index', compact('customers'));
    }

    public function create() { return view('customers.create'); }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:150', 'phone' => 'nullable', 'email' => 'nullable|email']);
        Customer::create($request->only(['name', 'phone', 'email', 'address']));
        return redirect()->route('customers.index')->with('success', 'Customer added.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['sales' => fn($q) => $q->latest()->limit(10)]);
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer) { return view('customers.edit', compact('customer')); }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($request->only(['name', 'phone', 'email', 'address']));
        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return back()->with('error', 'Cannot delete — customer has sales.');
        }
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    public function ledger(int $id)
    {
        $customer = Customer::with(['sales' => fn($q) => $q->latest()])->findOrFail($id);
        return view('customers.ledger', compact('customer'));
    }

    public function apiSearch(Request $request)
    {
        return response()->json(
            Customer::where('name', 'like', "%{$request->q}%")
                ->orWhere('phone', 'like', "%{$request->q}%")
                ->limit(10)
                ->get(['id', 'name', 'phone', 'loyalty_points'])
        );
    }

    // Create a customer from the POS screen and return it as JSON.
    public function apiStore(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
        ]);

        $customer = Customer::create($data);

        return response()->json([
            'id'    => $customer->id,
            'name'  => $customer->name,
            'phone' => $customer->phone,
        ], 201);
    }
}

// =========================================================
// Supplier Controller
// =========================================================
class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%"))
            ->orderBy('name')->paginate(20);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create() { return view('suppliers.create'); }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:200']);
        Supplier::create($request->only(['name', 'contact_person', 'phone', 'email', 'address', 'city']));
        return redirect()->route('suppliers.index')->with('success', 'Supplier added.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchases' => fn($q) => $q->latest()->limit(10)]);
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier) { return view('suppliers.edit', compact('supplier')); }
    public function update(Request $r, Supplier $s) { $s->update($r->only(['name','contact_person','phone','email','address','city'])); return redirect()->route('suppliers.show',$s)->with('success','Updated.'); }
    public function destroy(Supplier $s) { $s->delete(); return redirect()->route('suppliers.index')->with('success','Deleted.'); }
    public function ledger(int $id) { $supplier = Supplier::with(['purchases'])->findOrFail($id); return view('suppliers.ledger', compact('supplier')); }
}

// =========================================================
// Account (Cash & Bank) Controller
// =========================================================
class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::where('branch_id', auth()->user()->branch_id)->get();
        $totalBalance = $accounts->sum('balance');
        return view('accounts.index', compact('accounts', 'totalBalance'));
    }

    public function create() { return view('accounts.create'); }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required', 'type' => 'required|in:cash,bank']);
        Account::create([...$request->only(['name', 'type', 'account_number']), 'branch_id' => auth()->user()->branch_id, 'balance' => 0]);
        return redirect()->route('accounts.index')->with('success', 'Account added.');
    }

    public function show(Account $account) { return view('accounts.show', compact('account')); }
    public function edit(Account $account) { return view('accounts.edit', compact('account')); }
    public function update(Request $r, Account $a) { $a->update($r->only(['name','account_number','status'])); return redirect()->route('accounts.index')->with('success','Updated.'); }
    public function destroy(Account $account) { $account->delete(); return redirect()->route('accounts.index')->with('success','Deleted.'); }

    public function transactions(int $id)
    {
        $account  = Account::findOrFail($id);
        $payments = Payment::where('account_id', $id)->latest()->paginate(20);
        return view('accounts.transactions', compact('account', 'payments'));
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id|different:to_account_id',
            'to_account_id'   => 'required|exists:accounts,id',
            'amount'          => 'required|numeric|min:1',
        ]);

        $from = Account::findOrFail($request->from_account_id);
        if ($from->balance < $request->amount) {
            return back()->with('error', 'Insufficient balance.');
        }

        DB::transaction(function () use ($request, $from) {
            $refNo = 'TRF-' . strtoupper(Str::random(8));
            $from->decrement('balance', $request->amount);
            Account::find($request->to_account_id)->increment('balance', $request->amount);

            Payment::create(['reference_no' => $refNo, 'type' => 'transfer', 'account_id' => $request->from_account_id, 'to_account_id' => $request->to_account_id, 'amount' => $request->amount, 'method' => 'bank', 'notes' => $request->notes, 'created_by' => auth()->id()]);
        });

        return back()->with('success', 'Transfer completed.');
    }
}

// =========================================================
// Payment Controller
// =========================================================
class PaymentController extends Controller
{
    public function indexIn(Request $request)
    {
        $payments = Payment::with(['sale', 'account'])
            ->where('type', 'payment_in')
            ->whereHas('account', fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->latest()->paginate(20);

        $totals = ['cash' => Payment::where('type','payment_in')->where('method','cash')->sum('amount'), 'card' => Payment::where('type','payment_in')->where('method','card')->sum('amount')];

        return view('payments.in', compact('payments', 'totals'));
    }

    public function storeIn(Request $request)
    {
        $request->validate(['sale_id' => 'required|exists:sales,id', 'amount' => 'required|numeric|min:1', 'account_id' => 'required|exists:accounts,id']);
        DB::transaction(function () use ($request) {
            Payment::create([...$request->only(['sale_id','amount','method','account_id','notes']), 'reference_no' => 'PAY-'.strtoupper(Str::random(8)), 'type' => 'payment_in', 'party_type' => 'customer', 'created_by' => auth()->id()]);
            Account::find($request->account_id)->increment('balance', $request->amount);
            $sale = \App\Models\Sale::find($request->sale_id);
            $newPaid = $sale->paid_amount + $request->amount;
            $sale->update(['paid_amount' => min($newPaid, $sale->total), 'status' => $newPaid >= $sale->total ? 'paid' : 'partial']);
        });
        return back()->with('success', 'Payment recorded.');
    }

    public function indexOut(Request $request)
    {
        $payments = Payment::with(['purchase', 'account'])
            ->where('type', 'payment_out')
            ->whereHas('account', fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->latest()->paginate(20);

        return view('payments.out', compact('payments'));
    }

    public function storeOut(Request $request)
    {
        $request->validate(['purchase_id' => 'required|exists:purchases,id', 'amount' => 'required|numeric|min:1', 'account_id' => 'required|exists:accounts,id']);
        DB::transaction(function () use ($request) {
            Payment::create([...$request->only(['purchase_id','amount','method','account_id','notes']), 'reference_no' => 'PAY-'.strtoupper(Str::random(8)), 'type' => 'payment_out', 'party_type' => 'supplier', 'created_by' => auth()->id()]);
            Account::find($request->account_id)->decrement('balance', $request->amount);
            $purchase = Purchase::find($request->purchase_id);
            $newPaid = $purchase->paid_amount + $request->amount;
            $purchase->update(['paid_amount' => min($newPaid, $purchase->total), 'balance_due' => max(0, $purchase->total - $newPaid), 'payment_status' => $newPaid >= $purchase->total ? 'paid' : 'partial']);
        });
        return back()->with('success', 'Payment out recorded.');
    }
}

// =========================================================
// Expense Controller
// =========================================================
class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = Expense::with(['category', 'account'])
            ->where('branch_id', auth()->user()->branch_id)
            ->when($request->from_date, fn($q) => $q->whereDate('expense_date', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('expense_date', '<=', $request->to_date))
            ->latest('expense_date')->paginate(20);

        $categories = ExpenseCategory::all();
        $totals = ['month' => Expense::where('branch_id', auth()->user()->branch_id)->whereMonth('expense_date', now()->month)->sum('amount'), 'total' => Expense::where('branch_id', auth()->user()->branch_id)->sum('amount')];

        return view('expenses.index', compact('expenses', 'categories', 'totals'));
    }

    public function create() { return view('expenses.create', ['categories' => ExpenseCategory::all(), 'accounts' => Account::where('branch_id', auth()->user()->branch_id)->get()]); }

    public function store(Request $request)
    {
        $request->validate(['expense_category_id' => 'required|exists:expense_categories,id', 'description' => 'required', 'amount' => 'required|numeric|min:0.01', 'expense_date' => 'required|date']);
        Expense::create([...$request->only(['expense_category_id','account_id','description','amount','expense_date']), 'branch_id' => auth()->user()->branch_id, 'created_by' => auth()->id()]);
        if ($request->account_id) Account::find($request->account_id)->decrement('balance', $request->amount);
        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense) { return view('expenses.show', compact('expense')); }
    public function edit(Expense $expense) { return view('expenses.edit', compact('expense')); }
    public function update(Request $r, Expense $e) { $e->update($r->only(['description','amount','expense_date','expense_category_id'])); return redirect()->route('expenses.index')->with('success','Updated.'); }
    public function destroy(Expense $expense) { $expense->delete(); return redirect()->route('expenses.index')->with('success','Deleted.'); }
}

// =========================================================
// Barcode Controller
// =========================================================
class BarcodeController extends Controller
{
    public function print(Product $product)
    {
        $generator = new BarcodeGeneratorPNG();
        $barcode   = base64_encode($generator->getBarcode($product->barcode ?? $product->id, $generator::TYPE_CODE_128));
        $settings  = Setting::pluck('value', 'key_name');

        return view('barcodes.print', compact('product', 'barcode', 'settings'));
    }

    public function bulkPrint(Request $request)
    {
        $request->validate(['product_ids' => 'required|array', 'copies' => 'required|integer|min:1|max:100']);
        $products  = Product::whereIn('id', $request->product_ids)->get();
        $generator = new BarcodeGeneratorPNG();
        $settings  = Setting::pluck('value', 'key_name');

        $barcodes = $products->map(fn($p) => [
            'product' => $p,
            'barcode' => base64_encode($generator->getBarcode($p->barcode ?? $p->id, $generator::TYPE_CODE_128)),
            'copies'  => $request->copies,
        ]);

        return view('barcodes.bulk_print', compact('barcodes', 'settings'));
    }
}

// =========================================================
// Setting Controller
// =========================================================
class SettingController extends Controller
{
    public function index()
    {
        $settings  = Setting::pluck('value', 'key_name');
        $branches  = \App\Models\Branch::with('counters')->get();
        $users     = \App\Models\User::with('roles')->get();
        $counters  = \App\Models\Counter::with('branch')->get();

        return view('settings.index', compact('settings', 'branches', 'users', 'counters'));
    }

    public function save(Request $request)
    {
        foreach ($request->except(['_token', '_method']) as $key => $value) {
            Setting::updateOrCreate(['key_name' => $key], ['value' => $value]);
        }
        return back()->with('success', 'Settings saved.');
    }
}

// =========================================================
// Online Order Controller
// =========================================================
class OnlineOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = OnlineOrder::with('customer')
            ->where('branch_id', auth()->user()->branch_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(20);

        $stats = ['new' => OnlineOrder::where('status','new')->count(), 'processing' => OnlineOrder::where('status','processing')->count(), 'delivered' => OnlineOrder::where('status','delivered')->whereDate('updated_at', today())->count()];

        return view('online-orders.index', compact('orders', 'stats'));
    }

    public function show(OnlineOrder $onlineOrder)
    {
        $onlineOrder->load(['items.product', 'customer']);
        return view('online-orders.show', compact('onlineOrder'));
    }

    public function updateStatus(int $id)
    {
        OnlineOrder::findOrFail($id)->update(['status' => request('status')]);
        return back()->with('success', 'Order status updated.');
    }

    public function convertToSale(OnlineOrder $onlineOrder)
    {
        // Convert online order items to a regular sale
        $sale = app(SaleController::class)->store(request()->merge([
            'items' => $onlineOrder->items->map(fn($i) => [
                'product_id' => $i->product_id,
                'quantity'   => $i->quantity,
                'unit_price' => $i->unit_price,
            ])->toArray(),
            'customer_id'    => $onlineOrder->customer_id,
            'payment_method' => 'cash',
            'paid_amount'    => 0,
        ]));

        $onlineOrder->update(['status' => 'delivered']);
        return redirect()->route('online-orders.index')->with('success', 'Order converted to sale.');
    }

    public function create() { return view('online-orders.create'); }
    public function store(Request $r) { return back(); }
    public function edit(OnlineOrder $o) { return view('online-orders.edit', compact('o')); }
    public function update(Request $r, OnlineOrder $o) { return back(); }
    public function destroy(OnlineOrder $o) { $o->delete(); return back(); }
}

// =========================================================
// Purchase Return Controller
// =========================================================
class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = PurchaseReturn::with(['purchase.supplier'])
            ->whereHas('purchase', fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->latest()->paginate(20);

        return view('purchase-returns.index', compact('returns'));
    }

    public function create()
    {
        $purchases = Purchase::with('supplier')->where('branch_id', auth()->user()->branch_id)->latest()->limit(50)->get();
        return view('purchase-returns.create', compact('purchases'));
    }

    public function store(Request $request)
    {
        $request->validate(['purchase_id' => 'required|exists:purchases,id', 'return_amount' => 'required|numeric|min:0.01', 'reason' => 'required|string']);

        DB::transaction(function () use ($request) {
            $purchase = Purchase::findOrFail($request->purchase_id);
            PurchaseReturn::create([
                'dr_note_no'    => 'DR-' . str_pad(PurchaseReturn::count() + 1, 4, '0', STR_PAD_LEFT),
                'purchase_id'   => $request->purchase_id,
                'supplier_id'   => $purchase->supplier_id,
                'reason'        => $request->reason,
                'return_amount' => $request->return_amount,
                'status'        => 'pending',
                'created_by'    => auth()->id(),
            ]);
            Supplier::find($purchase->supplier_id)->decrement('balance_due', $request->return_amount);
        });

        return redirect()->route('purchase-returns.index')->with('success', 'Dr. Note issued.');
    }

    public function show(PurchaseReturn $purchaseReturn) { return view('purchase-returns.show', compact('purchaseReturn')); }
    public function edit(PurchaseReturn $p) { return view('purchase-returns.edit', compact('p')); }
    public function update(Request $r, PurchaseReturn $p) { return back(); }
    public function destroy(PurchaseReturn $p) { $p->delete(); return back(); }
}

// =========================================================
// Website Controller
// =========================================================
class WebsiteController extends Controller
{
    public function index()
    {
        $settings  = Setting::pluck('value', 'key_name');
        $banners   = Banner::orderBy('sort_order')->get();
        $products  = Product::where('show_in_online_store', true)->with('category')->limit(12)->get();

        return view('website.index', compact('settings', 'banners', 'products'));
    }

    public function saveSettings(Request $request)
    {
        foreach ($request->except(['_token']) as $key => $value) {
            Setting::updateOrCreate(['key_name' => $key], ['value' => $value]);
        }
        return back()->with('success', 'Website settings saved.');
    }
}
