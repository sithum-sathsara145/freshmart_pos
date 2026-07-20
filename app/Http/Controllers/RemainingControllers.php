<?php

namespace App\Http\Controllers;

use App\Support\CurrentBranch;
use App\Support\DocumentNumber;
use App\Support\Spreadsheet;


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
use Picqer\Barcode\BarcodeGeneratorSVG;


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
        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email',
            'address'      => 'nullable|string',
            'nic'          => 'nullable|string|max:30',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);
        $data['credit_approved'] = $request->boolean('credit_approved');
        Customer::create($data);
        return redirect()->route('customers.index')->with('success', 'Customer added.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['sales' => fn($q) => $q->latest()->limit(10)]);

        // Credit history = sales that carried a credit portion (paid off or not), plus any
        // still-unpaid sale so nothing owed is ever hidden. Newest first.
        $creditSales = $customer->sales()
            ->where(fn($q) => $q->where('credit_amount', '>', 0)->orWhereColumn('paid_amount', '<', 'total'))
            ->latest()
            ->get();

        // Accounts the repayment can be received into (branch cash/bank).
        $accounts = Account::whereBranch(CurrentBranch::id())
            ->whereIn('type', ['cash', 'bank'])
            ->orderBy('name')
            ->get();

        return view('customers.show', compact('customer', 'creditSales', 'accounts'));
    }

    public function edit(Customer $customer) { return view('customers.edit', compact('customer')); }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email',
            'address'      => 'nullable|string',
            'nic'          => 'nullable|string|max:30',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);
        $data['credit_approved'] = $request->boolean('credit_approved');
        $customer->update($data);
        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        // Sales, returns and online orders all hold FK references to the customer.
        if ($customer->sales()->exists()
            || \App\Models\SaleReturn::where('customer_id', $customer->id)->exists()
            || OnlineOrder::where('customer_id', $customer->id)->exists()) {
            return back()->with('error', 'Cannot delete — this customer has sales, returns or online orders on record.');
        }
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    public function apiSearch(Request $request)
    {
        return response()->json(
            Customer::where('name', 'like', "%{$request->q}%")
                ->orWhere('phone', 'like', "%{$request->q}%")
                ->orWhere('nic', 'like', "%{$request->q}%")
                ->limit(10)
                ->get(['id', 'name', 'phone', 'nic', 'address', 'credit_approved', 'credit_limit', 'loyalty_points'])
        );
    }

    // Create a customer from the POS screen and return it as JSON.
    public function apiStore(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:150',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email',
            'nic'     => 'nullable|string|max:30',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create($data);

        return response()->json([
            'id'              => $customer->id,
            'name'            => $customer->name,
            'phone'           => $customer->phone,
            'nic'             => $customer->nic,
            'address'         => $customer->address,
            'credit_approved' => (bool) $customer->credit_approved,
            'credit_limit'    => $customer->credit_limit,
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
    public function destroy(Supplier $s)
    {
        if (Purchase::where('supplier_id', $s->id)->exists() || PurchaseReturn::where('supplier_id', $s->id)->exists()) {
            return back()->with('error', 'Cannot delete — this supplier has purchases or returns on record.');
        }
        $s->delete();
        return redirect()->route('suppliers.index')->with('success', 'Deleted.');
    }

    // ── CSV / Excel ──────────────────────────────────────────────────────────
    //
    // total_purchases and balance_due are deliberately absent: they are derived
    // from purchase records, so letting a spreadsheet set them would put the
    // supplier ledger out of step with the purchases behind it.

    private const IMPORT_COLUMNS = ['name', 'contact_person', 'phone', 'email', 'address', 'city'];

    public function importForm()
    {
        return view('suppliers.import', ['result' => null]);
    }

    public function importSample(Request $request)
    {
        $format  = $request->get('format') === 'xlsx' ? 'xlsx' : 'csv';
        $samples = [
            ['Ceylon Foods (Pvt) Ltd', 'Nimal Perera',  '0112345678', 'orders@ceylonfoods.lk', '45 Galle Road', 'Colombo'],
            ['Lanka Dairy Supplies',   'Kamala Silva',  '0771234567', '',                      '12 Kandy Road', 'Kadawatha'],
            ['Fresh Veg Traders',      '',              '0812223344', '',                      '',             'Kandy'],
        ];

        return Spreadsheet::download(self::IMPORT_COLUMNS, $samples, $format, 'suppliers_sample');
    }

    // Export in the import column layout, so the file can be edited and sent back in.
    public function export(Request $request)
    {
        $format = $request->get('format') === 'xlsx' ? 'xlsx' : 'csv';

        $rows = Supplier::when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            }))
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [$s->name, $s->contact_person, $s->phone, $s->email, $s->address, $s->city])
            ->all();

        return Spreadsheet::download(self::IMPORT_COLUMNS, $rows, $format, 'suppliers_export_' . date('Ymd_His'));
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|max:10240']);

        $file = $request->file('file');
        $type = Spreadsheet::typeFor($file->getClientOriginalExtension());
        if (! $type) {
            return back()->with('error', 'Unsupported file. Please upload a .csv or .xlsx file.');
        }

        try {
            $rows = Spreadsheet::read($file->getRealPath(), $type);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not read the file: ' . $e->getMessage());
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = [];
        $seen    = [];

        foreach ($rows as $n => $r) {
            $line = $n + 2;                      // +1 header row, +1 to make it 1-based
            $name = trim((string) ($r['name'] ?? ''));

            if ($name === '') {
                $errors[] = "Row {$line}: missing supplier name — skipped.";
                continue;
            }

            $phone = trim((string) ($r['phone'] ?? ''));
            $email = trim((string) ($r['email'] ?? ''));

            // A supplier is the same supplier if the phone matches, else the email,
            // else the name — phone first because it is the field least likely to
            // be shared between two genuinely different companies.
            $key = $phone !== '' ? "p:$phone" : ($email !== '' ? "e:" . mb_strtolower($email) : "n:" . mb_strtolower($name));
            if (isset($seen[$key])) {
                $skipped++;
                continue;
            }
            $seen[$key] = true;

            if ($phone !== '') {
                $existing = Supplier::where('phone', $phone)->first();
            } elseif ($email !== '') {
                $existing = Supplier::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
            } else {
                $existing = Supplier::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            }

            $fields = [];
            foreach (self::IMPORT_COLUMNS as $col) {
                if (Spreadsheet::has($r, $col)) {
                    $fields[$col] = trim((string) $r[$col]);
                }
            }

            try {
                if ($existing) {
                    $existing->update($fields);   // blanks were dropped above, so nothing gets wiped
                    $updated++;
                } else {
                    Supplier::create($fields + ['name' => $name]);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Row {$line}: {$e->getMessage()}";
            }
        }

        return view('suppliers.import', ['result' => compact('created', 'updated', 'skipped', 'errors')]);
    }
}

// =========================================================
// Account (Cash & Bank) Controller
// =========================================================
class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::whereBranch(CurrentBranch::id())->get();
        $totalBalance = $accounts->sum('balance');
        return view('accounts.index', compact('accounts', 'totalBalance'));
    }

    public function create() { return view('accounts.create'); }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required', 'type' => 'required|in:cash,bank']);
        if (! $branchId = CurrentBranch::requireId()) {
            return back()->withInput()->with('error', CurrentBranch::pickBranchMessage());
        }
        Account::create([...$request->only(['name', 'type', 'account_number']), 'branch_id' => $branchId, 'balance' => 0]);
        return redirect()->route('accounts.index')->with('success', 'Account added.');
    }

    public function destroy(Account $account)
    {
        if (Payment::where('account_id', $account->id)->orWhere('to_account_id', $account->id)->exists()
            || Expense::where('account_id', $account->id)->exists()) {
            return back()->with('error', 'Cannot delete — this account has payments or expenses on record.');
        }
        if (abs((float) $account->balance) > 0.004) {
            return back()->with('error', 'Cannot delete — the account still holds a balance. Transfer it out first.');
        }
        $account->delete();
        return redirect()->route('accounts.index')->with('success', 'Deleted.');
    }

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
        $branchId = CurrentBranch::id();

        $payments = Payment::with(['sale', 'account'])
            ->where('type', 'payment_in')
            ->whereHas('account', fn($q) => $q->whereBranch($branchId))
            ->latest()->paginate(20);

        // Scope the summary cards to this branch (were summing every branch).
        $inBranch = fn($q) => $q->where('type', 'payment_in')->whereHas('account', fn($a) => $a->whereBranch($branchId));
        $totals = [
            'cash' => Payment::where($inBranch)->where('method', 'cash')->sum('amount'),
            'card' => Payment::where($inBranch)->where('method', 'card')->sum('amount'),
        ];

        return view('payments.in', compact('payments', 'totals'));
    }

    public function storeIn(Request $request)
    {
        $branchId = CurrentBranch::id();
        $request->validate(['sale_id' => 'required|exists:sales,id', 'amount' => 'required|numeric|min:1', 'account_id' => 'required|exists:accounts,id']);

        $sale    = \App\Models\Sale::whereBranch($branchId)->find($request->sale_id);
        $account = Account::whereBranch($branchId)->find($request->account_id);
        if (! $sale || ! $account) {
            return back()->with('error', 'Sale or account not found for this branch.');
        }
        $due = max(0, (float) $sale->total - (float) $sale->paid_amount);
        if ($due <= 0) {
            return back()->with('error', 'This invoice is already fully paid.');
        }
        // Never credit more than is actually owed (prevents overstating the account balance).
        $amount = min((float) $request->amount, $due);
        $method = in_array($request->method, ['cash', 'card', 'bank', 'cheque'], true) ? $request->method : 'cash';

        DB::transaction(function () use ($request, $sale, $account, $amount, $method) {
            Payment::create([
                'reference_no' => 'PAY-' . strtoupper(Str::random(8)),
                'type'         => 'payment_in',
                'account_id'   => $account->id,
                'party_type'   => 'customer',
                'party_id'     => $sale->customer_id,
                'sale_id'      => $sale->id,
                'amount'       => $amount,
                'method'       => $method,
                'notes'        => $request->notes,
                'created_by'   => auth()->id(),
            ]);
            $account->increment('balance', $amount);
            $newPaid = (float) $sale->paid_amount + $amount;
            $sale->update(['paid_amount' => $newPaid, 'status' => $newPaid >= $sale->total ? 'paid' : 'partial']);
        });

        return back()->with('success', 'Payment of Rs. ' . number_format($amount, 2) . ' recorded.');
    }

    public function indexOut(Request $request)
    {
        $payments = Payment::with(['purchase', 'account'])
            ->where('type', 'payment_out')
            ->whereHas('account', fn($q) => $q->whereBranch(CurrentBranch::id()))
            ->latest()->paginate(20);

        return view('payments.out', compact('payments'));
    }

    public function storeOut(Request $request)
    {
        $branchId = CurrentBranch::id();
        $request->validate(['purchase_id' => 'required|exists:purchases,id', 'amount' => 'required|numeric|min:1', 'account_id' => 'required|exists:accounts,id']);

        $purchase = Purchase::whereBranch($branchId)->find($request->purchase_id);
        $account  = Account::whereBranch($branchId)->find($request->account_id);
        if (! $purchase || ! $account) {
            return back()->with('error', 'Purchase or account not found for this branch.');
        }
        $due = max(0, (float) $purchase->total - (float) $purchase->paid_amount);
        if ($due <= 0) {
            return back()->with('error', 'This purchase is already fully paid.');
        }
        $amount = min((float) $request->amount, $due);
        $method = in_array($request->method, ['cash', 'card', 'bank', 'cheque'], true) ? $request->method : 'cash';

        DB::transaction(function () use ($request, $purchase, $account, $amount, $method) {
            Payment::create([
                'reference_no' => 'PAY-' . strtoupper(Str::random(8)),
                'type'         => 'payment_out',
                'account_id'   => $account->id,
                'party_type'   => 'supplier',
                'party_id'     => $purchase->supplier_id,
                'purchase_id'  => $purchase->id,
                'amount'       => $amount,
                'method'       => $method,
                'notes'        => $request->notes,
                'created_by'   => auth()->id(),
            ]);
            $account->decrement('balance', $amount);
            $newPaid = (float) $purchase->paid_amount + $amount;
            $purchase->update([
                'paid_amount'    => $newPaid,
                'balance_due'    => max(0, (float) $purchase->total - $newPaid),
                'payment_status' => $newPaid >= $purchase->total ? 'paid' : 'partial',
            ]);
        });

        return back()->with('success', 'Payment of Rs. ' . number_format($amount, 2) . ' recorded.');
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
            ->whereBranch(CurrentBranch::id())
            ->when($request->from_date, fn($q) => $q->whereDate('expense_date', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('expense_date', '<=', $request->to_date))
            ->latest('expense_date')->paginate(20);

        $categories = ExpenseCategory::all();
        $totals = ['month' => Expense::whereBranch(CurrentBranch::id())->whereMonth('expense_date', now()->month)->sum('amount'), 'total' => Expense::whereBranch(CurrentBranch::id())->sum('amount')];

        return view('expenses.index', compact('expenses', 'categories', 'totals'));
    }

    public function create() { return view('expenses.create', ['categories' => ExpenseCategory::all(), 'accounts' => Account::whereBranch(CurrentBranch::id())->get()]); }

    public function store(Request $request)
    {
        $request->validate(['expense_category_id' => 'required|exists:expense_categories,id', 'description' => 'required', 'amount' => 'required|numeric|min:0.01', 'expense_date' => 'required|date']);
        if (! $branchId = CurrentBranch::requireId()) {
            return back()->withInput()->with('error', CurrentBranch::pickBranchMessage());
        }
        Expense::create([...$request->only(['expense_category_id','account_id','description','amount','expense_date']), 'branch_id' => $branchId, 'created_by' => auth()->id()]);
        if ($request->account_id) Account::find($request->account_id)->decrement('balance', $request->amount);
        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense)
    {
        CurrentBranch::guard($expense->branch_id);
        return view('expenses.edit', [
            'expense'    => $expense,
            'categories' => ExpenseCategory::all(),
        ]);
    }

    public function update(Request $r, Expense $e)
    {
        CurrentBranch::guard($e->branch_id);
        $r->validate(['description' => 'required', 'amount' => 'required|numeric|min:0.01', 'expense_date' => 'required|date', 'expense_category_id' => 'required|exists:expense_categories,id']);

        DB::transaction(function () use ($r, $e) {
            // Keep the paying account in step when the amount changes.
            $delta = (float) $r->amount - (float) $e->amount;
            if ($e->account_id && abs($delta) > 0.004) {
                Account::where('id', $e->account_id)->decrement('balance', $delta);
            }
            $e->update($r->only(['description', 'amount', 'expense_date', 'expense_category_id']));
        });

        return redirect()->route('expenses.index')->with('success', 'Updated.');
    }

    public function destroy(Expense $expense)
    {
        CurrentBranch::guard($expense->branch_id);

        DB::transaction(function () use ($expense) {
            // Put the money back in the account it was paid from.
            if ($expense->account_id) {
                Account::where('id', $expense->account_id)->increment('balance', $expense->amount);
            }
            $expense->delete();
        });

        return redirect()->route('expenses.index')->with('success', 'Expense deleted and account restored.');
    }
}

// =========================================================
// Barcode Controller
// =========================================================
class BarcodeController extends Controller
{
    public function print(Product $product)
    {
        $generator = new BarcodeGeneratorSVG();
        $barcode   = base64_encode($generator->getBarcode($product->barcode ?? $product->id, $generator::TYPE_CODE_128));
        $settings  = Setting::pluck('value', 'key_name');

        return view('barcodes.print', compact('product', 'barcode', 'settings'));
    }

    // Product picker for printing many barcode labels at once.
    public function labels()
    {
        $products = Product::with('category')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'barcode', 'sale_price', 'category_id', 'status']);

        $categories = \App\Models\Category::orderBy('name')->get(['id', 'name']);

        return view('barcodes.labels', compact('products', 'categories'));
    }

    public function bulkPrint(Request $request)
    {
        $request->validate([
            'product_ids'    => 'required|array|min:1',
            'product_ids.*'  => 'integer|exists:products,id',
            'copies'         => 'nullable|array',
            'default_copies' => 'nullable|integer|min:1|max:200',
            'label_size'     => 'nullable|in:roll58,roll40,a4',
        ]);

        $default   = (int) $request->input('default_copies', 1);
        $copiesMap = $request->input('copies', []);
        $products  = Product::whereIn('id', $request->product_ids)->orderBy('name')->get();
        $generator = new BarcodeGeneratorSVG();
        $settings  = Setting::pluck('value', 'key_name');

        $barcodes = $products->map(function ($p) use ($generator, $copiesMap, $default) {
            $copies = (int) ($copiesMap[$p->id] ?? $default);
            return [
                'product' => $p,
                'barcode' => base64_encode($generator->getBarcode($p->barcode ?? $p->id, $generator::TYPE_CODE_128)),
                'copies'  => max(1, min(200, $copies)),
            ];
        })->values();

        $labelSize = $request->input('label_size', 'a4');
        $showPrice = $request->boolean('show_price', true);
        $showName  = $request->boolean('show_name', true);

        return view('barcodes.bulk_print', compact('barcodes', 'settings', 'labelSize', 'showPrice', 'showName'));
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
        $counters  = \App\Models\Counter::with('branch')->get();

        // Users tab — super_admin accounts are invisible to everyone else, and
        // people who can't see all branches only see their own branch's staff.
        $actor = auth()->user();
        $users = \App\Models\User::with('roles', 'branch', 'staff')
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereDoesntHave(
                'roles',
                fn ($r) => $r->where('name', \App\Models\Role::SUPER_ADMIN)
            ))
            ->when(! $actor->seesAllBranches(), fn ($q) => $q->where('branch_id', $actor->branch_id))
            ->orderBy('name')
            ->get();

        // Roles this actor may hand out (at or below their rank; never super_admin).
        $assignableRoles = \App\Models\Role::assignableBy($actor)->orderByDesc('level')->get();

        // API keys tab — never expose secret values to the view; only whether they are set.
        $apiCredentials = config('api_credentials', []);
        $apiKeyState    = [];
        foreach ($apiCredentials as $group) {
            foreach ($group['fields'] as $key => $field) {
                $apiKeyState[$key] = empty($field['secret'])
                    ? ['value' => Setting::get($key, '')]
                    : ['set' => Setting::has($key)];
            }
        }

        return view('settings.index', compact(
            'settings', 'branches', 'users', 'counters', 'assignableRoles', 'apiCredentials', 'apiKeyState'
        ));
    }

    public function save(Request $request)
    {
        // Per-counter floats live on the counters table, not in settings.
        foreach ($request->input('counter_float', []) as $counterId => $amount) {
            \App\Models\Counter::where('id', $counterId)
                ->update(['float_amount' => max(0, (float) $amount)]);
        }

        // Never let API-key fields flow through the generic plaintext save.
        $reserved = collect(config('api_credentials', []))
            ->flatMap(fn ($g) => array_keys($g['fields']))
            ->flatMap(fn ($k) => [$k, $k . '_clear'])
            ->all();

        foreach ($request->except(array_merge(['_token', '_method', 'counter_float'], $reserved)) as $key => $value) {
            Setting::updateOrCreate(['key_name' => $key], ['value' => $value]);
        }
        return back()->with('success', 'Settings saved.');
    }

    // Secret values are encrypted at rest; blank fields keep the existing value.
    public function saveApiKeys(Request $request)
    {
        foreach (config('api_credentials', []) as $group) {
            foreach ($group['fields'] as $key => $field) {
                if (! empty($field['secret'])) {
                    if ($request->boolean($key . '_clear')) {
                        Setting::putSecret($key, null);          // delete the stored secret
                    } elseif (filled($request->input($key))) {
                        Setting::putSecret($key, $request->input($key));
                    }
                    // blank + not cleared → leave the current value untouched
                } else {
                    Setting::put($key, (string) $request->input($key, ''));
                }
            }
        }

        return redirect()->to(route('settings.index') . '#apikeys')->with('success', 'API keys saved.');
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
            ->whereBranch(CurrentBranch::id())
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
        // Convert online order items to a regular sale.
        $response = app(SaleController::class)->store(request()->merge([
            'items' => $onlineOrder->items->map(fn($i) => [
                'product_id' => $i->product_id,
                'quantity'   => $i->quantity,
                'unit_price' => $i->unit_price,
            ])->toArray(),
            'customer_id'    => $onlineOrder->customer_id,
            'payment_method' => 'cash',
            'paid_amount'    => 0,
        ]));

        // Only mark the order delivered when the sale actually saved — on failure,
        // store() redirects back with an error instead of to the new invoice.
        if (! str_contains($response->getTargetUrl(), '/sales/')) {
            return $response;
        }

        $onlineOrder->update(['status' => 'delivered']);
        return redirect()->route('online-orders.index')->with('success', 'Order converted to sale.');
    }

    public function destroy(OnlineOrder $o)
    {
        DB::transaction(function () use ($o) {
            $o->items()->delete();   // FK: online_order_items reference the order
            $o->delete();
        });
        return back()->with('success', 'Online order deleted.');
    }
}

// =========================================================
// Purchase Return Controller
// =========================================================
class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = PurchaseReturn::with(['purchase.supplier'])
            ->whereHas('purchase', fn($q) => $q->whereBranch(CurrentBranch::id()))
            ->latest()->paginate(20);

        return view('purchase-returns.index', compact('returns'));
    }

    public function create()
    {
        $branchId = CurrentBranch::id();

        // Bills with at least one line that still has stock on hand from this purchase
        // (returnable qty = the line's remaining cost layer). Embedded for the picker.
        $purchases = Purchase::with(['supplier', 'items.product', 'items.layer'])
            ->whereBranch($branchId)
            ->latest()->limit(100)->get()
            ->map(function ($p) {
                $lines = $p->items->map(function ($it) {
                        $remaining = $it->layer ? (float) $it->layer->qty_remaining : 0.0;
                        return [
                            'purchase_item_id' => $it->id,
                            'name'             => $it->product?->name ?? 'Item',
                            'purchased'        => (float) $it->quantity,
                            'remaining'        => round($remaining, 3),
                            'unit_price'       => (float) $it->unit_price,
                        ];
                    })
                    ->filter(fn($l) => $l['remaining'] > 0.0005)
                    ->values();

                return [
                    'id'       => $p->id,
                    'bill_no'  => $p->bill_no,
                    'supplier' => $p->supplier?->name ?? '—',
                    'total'    => (float) $p->total,
                    'lines'    => $lines,
                ];
            })
            ->filter(fn($p) => count($p['lines']) > 0)
            ->values();

        return view('purchase-returns.create', compact('purchases'));
    }

    public function store(Request $request)
    {
        $branchId = CurrentBranch::id();
        $request->validate([
            'purchase_id'              => 'required|exists:purchases,id',
            'reason'                   => 'required|string',
            'credit_method'            => 'required|in:credit_note,cash_refund,replacement',
            'items'                    => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.quantity'         => 'nullable|numeric|min:0',
        ]);

        $purchase = Purchase::with(['items.layer', 'items.product'])
            ->whereBranch($branchId)->find($request->purchase_id);
        if (! $purchase) {
            return back()->with('error', 'That purchase belongs to another branch.')->withInput();
        }
        $purchaseItems = $purchase->items->keyBy('id');

        // Keep rows with a positive qty; cap each at the line's on-hand layer qty.
        $lines = [];
        foreach ($request->items as $row) {
            $qty = (float) ($row['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $pi = $purchaseItems->get((int) $row['purchase_item_id']);
            if (! $pi) {
                return back()->with('error', 'A returned item does not belong to the selected bill.')->withInput();
            }
            $onHand = $pi->layer ? (float) $pi->layer->qty_remaining : 0.0;
            if ($qty - $onHand > 0.0005) {
                $name = $pi->product?->name ?? 'item';
                return back()->with('error', "You can return at most {$onHand} of \"{$name}\" (the rest was sold or already returned).")->withInput();
            }
            $lines[] = ['pi' => $pi, 'qty' => $qty];
        }
        if (empty($lines)) {
            return back()->with('error', 'Enter a return quantity for at least one item.')->withInput();
        }

        DB::transaction(function () use ($request, $branchId, $purchase, $lines) {
            $returnAmount = round(collect($lines)->sum(fn($l) => $l['qty'] * (float) $l['pi']->unit_price), 2);

            $return = PurchaseReturn::create([
                'dr_note_no'    => DocumentNumber::next('debit_note'),
                'purchase_id'   => $purchase->id,
                'supplier_id'   => $purchase->supplier_id,
                'reason'        => $request->reason,
                'return_amount' => $returnAmount,
                'credit_method' => $request->credit_method,
                'status'        => $request->credit_method === 'replacement' ? 'pending' : 'credited',
                'created_by'    => auth()->id(),
            ]);

            foreach ($lines as $l) {
                $pi       = $l['pi'];
                $qty      = $l['qty'];
                $layer    = $pi->layer;
                $unitCost = $layer ? (float) $layer->cost : (float) $pi->unit_price;

                \App\Models\PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id'         => $pi->product_id,
                    'purchase_item_id'   => $pi->id,
                    'quantity'           => $qty,
                    'unit_price'         => $pi->unit_price,
                    'cost'               => round($unitCost * $qty, 2),
                    'subtotal'           => round($qty * (float) $pi->unit_price, 2),
                ]);

                // Goods go back to the supplier: drop the line's layer + aggregate together.
                if ($layer) {
                    $layer->decrement('qty_remaining', $qty);
                }
                Stock::where('product_id', $pi->product_id)->whereBranch($branchId)->decrement('quantity', $qty);
            }

            // Settle the credit per the chosen method.
            if ($request->credit_method === 'credit_note') {
                // Reduce what we still owe the supplier (never below zero).
                if ($supplier = Supplier::find($purchase->supplier_id)) {
                    $dec = min((float) $supplier->balance_due, $returnAmount);
                    if ($dec > 0) {
                        $supplier->decrement('balance_due', $dec);
                    }
                }
            } elseif ($request->credit_method === 'cash_refund') {
                // Supplier hands cash back -> money into the till.
                $account = Account::whereBranch($branchId)->where('type', 'cash')->first()
                        ?? Account::whereBranch($branchId)->first();
                if ($account) {
                    Payment::create([
                        'reference_no' => 'REF-' . strtoupper(Str::random(8)),
                        'type'         => 'payment_in',
                        'account_id'   => $account->id,
                        'party_type'   => 'supplier',
                        'party_id'     => $purchase->supplier_id,
                        'purchase_id'  => $purchase->id,
                        'amount'       => $returnAmount,
                        'method'       => 'cash',
                        'notes'        => "Refund for {$return->dr_note_no}",
                        'created_by'   => auth()->id(),
                    ]);
                    $account->increment('balance', $returnAmount);
                }
            }
            // 'replacement': goods returned, a fresh GRN brings the replacement — no money movement.
        });

        return redirect()->route('purchase-returns.index')->with('success', 'Dr. Note issued.');
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['items.product', 'purchase.supplier', 'supplier']);
        abort_if(! $purchaseReturn->purchase, 404);
        CurrentBranch::guard($purchaseReturn->purchase->branch_id);
        return view('purchase-returns.show', compact('purchaseReturn'));
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['items', 'purchase']);
        $purchase = $purchaseReturn->purchase;
        if (! $purchase || ! CurrentBranch::allows($purchase->branch_id)) {
            return back()->with('error', 'Return not found for this branch.');
        }

        // Goods go back to the branch that bought them, not the branch being viewed.
        $branchId = (int) $purchase->branch_id;

        DB::transaction(function () use ($purchaseReturn, $purchase, $branchId) {
            foreach ($purchaseReturn->items as $item) {
                // Put the goods back on hand: restore the original layer if it still exists,
                // otherwise add a fresh layer at the captured cost.
                $layer = $item->purchase_item_id ? optional(\App\Models\PurchaseItem::find($item->purchase_item_id))->layer : null;
                if ($layer) {
                    $layer->increment('qty_remaining', (float) $item->quantity);
                    Stock::where('product_id', $item->product_id)->whereBranch($branchId)->increment('quantity', (float) $item->quantity);
                } elseif ($product = Product::find($item->product_id)) {
                    $perUnit = ($item->cost !== null && (float) $item->quantity > 0)
                        ? (float) $item->cost / (float) $item->quantity
                        : (float) $item->unit_price;
                    \App\Support\Inventory::addLayer($item->product_id, $branchId, (float) $item->quantity, $perUnit, (float) ($product->sale_price ?? 0), 'DR-REVERSE', now()->toDateString());
                }
            }

            // Undo the credit that was applied.
            if ($purchaseReturn->credit_method === 'credit_note') {
                if ($s = Supplier::find($purchase->supplier_id)) {
                    $s->increment('balance_due', $purchaseReturn->return_amount);
                }
            } elseif ($purchaseReturn->credit_method === 'cash_refund') {
                $pays = Payment::where('purchase_id', $purchase->id)->where('type', 'payment_in')
                    ->where('notes', 'like', '%' . $purchaseReturn->dr_note_no . '%')->get();
                foreach ($pays as $p) {
                    Account::where('id', $p->account_id)->decrement('balance', $p->amount);
                    $p->delete();
                }
            }

            $purchaseReturn->items()->delete();
            $purchaseReturn->delete();
        });

        return redirect()->route('purchase-returns.index')->with('success', 'Purchase return reversed.');
    }

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
