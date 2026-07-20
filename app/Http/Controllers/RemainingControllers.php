<?php

namespace App\Http\Controllers;

use App\Support\CurrentBranch;
use App\Support\DocumentNumber;
use App\Support\Ledger;
use App\Support\Spreadsheet;


use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Account;
use App\Models\Branch;
use App\Models\AccountTransaction;
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
        // Accounts with no branch belong to the business rather than one shop,
        // so they show wherever you are working.
        $accounts = Account::with('branch')
            ->where(fn ($q) => $q->whereBranch(CurrentBranch::id())->orWhereNull('branch_id'))
            ->orderBy('type')->orderBy('name')
            ->get();

        return view('accounts.index', [
            'cashBooks'    => $accounts->where('type', 'cash'),
            'bankAccounts' => $accounts->where('type', 'bank'),
            'cashTotal'    => $accounts->where('type', 'cash')->sum('balance'),
            'bankTotal'    => $accounts->where('type', 'bank')->sum('balance'),
            'totalBalance' => $accounts->sum('balance'),
            'transferable' => $accounts->where('status', 'active'),
        ]);
    }

    public function create()
    {
        return view('accounts.form', ['account' => new Account(['type' => 'cash', 'status' => 'active']), 'branches' => Branch::orderBy('name')->get()]);
    }

    public function edit(Account $account)
    {
        return view('accounts.form', ['account' => $account, 'branches' => Branch::orderBy('name')->get()]);
    }

    /** Bank details only make sense on a bank account; a cash book has none. */
    private function accountRules(): array
    {
        return [
            'name'            => 'required|string|max:150',
            'type'            => 'required|in:cash,bank',
            'branch_id'       => 'nullable|exists:branches,id',
            'subtype'         => 'nullable|required_if:type,bank|in:savings,current',
            'bank_name'       => 'nullable|required_if:type,bank|string|max:150',
            'bank_branch'     => 'nullable|string|max:150',
            'account_number'  => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric',
            'is_cashier_book' => 'nullable|boolean',
            'notes'           => 'nullable|string|max:255',
            'status'          => 'nullable|in:active,inactive',
        ];
    }

    private function accountFields(Request $request): array
    {
        $fields = $request->only([
            'name', 'type', 'branch_id', 'subtype', 'bank_name', 'bank_branch',
            'account_number', 'notes', 'status',
        ]);

        $fields['branch_id']       = $request->branch_id ?: null;   // blank = not tied to a branch
        $fields['status']          = $request->status ?: 'active';
        $fields['is_cashier_book'] = $request->boolean('is_cashier_book');

        // A cash book carries no bank details, whatever was left in the form.
        if ($fields['type'] === 'cash') {
            $fields['subtype'] = $fields['bank_name'] = $fields['bank_branch'] = null;
        }

        return $fields;
    }

    public function store(Request $request)
    {
        $request->validate($this->accountRules());

        $opening = round((float) ($request->opening_balance ?? 0), 2);

        $account = Account::create($this->accountFields($request) + [
            'opening_balance' => $opening,
            'balance'         => 0,
        ]);

        // The opening balance is money the account starts with, so it belongs on
        // the statement as its first line rather than appearing from nowhere.
        if (abs($opening) > 0.004) {
            $opening > 0
                ? Ledger::credit($account, $opening, ['description' => 'Opening balance', 'source_type' => 'opening'])
                : Ledger::debit($account, -$opening, ['description' => 'Opening balance', 'source_type' => 'opening']);
        }

        return redirect()->route('accounts.index')->with('success', 'Account added.');
    }

    public function update(Request $request, Account $account)
    {
        $request->validate($this->accountRules());

        // Opening balance is deliberately not editable: it is already a ledger
        // entry, and changing it here would put the statement out of step.
        $account->update($this->accountFields($request));

        return redirect()->route('accounts.index')->with('success', 'Account updated.');
    }

    public function destroy(Account $account)
    {
        if (AccountTransaction::where('account_id', $account->id)
                ->where('source_type', '!=', 'opening')->exists()) {
            return back()->with('error', 'Cannot delete — this account has movements on record. Mark it inactive instead.');
        }
        if (abs((float) $account->balance) > 0.004) {
            return back()->with('error', 'Cannot delete — the account still holds a balance. Transfer it out first.');
        }

        AccountTransaction::where('account_id', $account->id)->delete();
        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Deleted.');
    }

    /** A deposit or withdrawal entered by hand — a correction, or cash put in from outside. */
    public function entry(Request $request, Account $account)
    {
        $request->validate([
            'direction'   => 'required|in:credit,debit',
            'amount'      => 'required|numeric|min:0.01',
            'occurred_at' => 'nullable|date',
            'description' => 'required|string|max:255',
        ]);

        $amount = round((float) $request->amount, 2);

        if ($request->direction === 'debit' && (float) $account->balance + 0.0001 < $amount) {
            return back()->withInput()->with('error',
                'Not enough in this account — it holds Rs. ' . number_format((float) $account->balance, 2) . '.');
        }

        $meta = [
            'description' => $request->description,
            'source_type' => 'manual',
            'occurred_at' => $request->occurred_at ?: now(),
        ];

        $request->direction === 'credit'
            ? Ledger::credit($account, $amount, $meta)
            : Ledger::debit($account, $amount, $meta);

        return back()->with('success', $request->direction === 'credit' ? 'Deposit recorded.' : 'Withdrawal recorded.');
    }

    /** A bank-style statement: opening balance, every movement, closing balance. */
    public function transactions(Request $request, int $id)
    {
        $account = Account::with('branch')->findOrFail($id);

        $from = $request->from_date ?: null;
        $to   = $request->to_date ?: null;

        $inPeriod = fn ($q) => $q->where('account_id', $account->id)
            ->when($from, fn ($q) => $q->whereDate('occurred_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('occurred_at', '<=', $to));

        // What the account held before the window opened.
        $opening = $from
            ? (float) AccountTransaction::where('account_id', $account->id)
                ->whereDate('occurred_at', '<', $from)
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0) AS t")
                ->value('t')
            : 0.0;

        $entries = AccountTransaction::with('counterparty')
            ->where($inPeriod)
            ->orderBy('occurred_at')->orderBy('id')
            ->paginate(50)->withQueryString();

        $totals = AccountTransaction::where($inPeriod)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) AS money_in,
                COALESCE(SUM(CASE WHEN direction = 'debit'  THEN amount ELSE 0 END), 0) AS money_out
            ")->first();

        return view('accounts.transactions', [
            'account'  => $account,
            'entries'  => $entries,
            'opening'  => $opening,
            'moneyIn'  => (float) $totals->money_in,
            'moneyOut' => (float) $totals->money_out,
            'from'     => $from,
            'to'       => $to,
            // The stored balance should equal what the entries add up to; if it
            // ever doesn't, the statement says so rather than quietly disagreeing.
            'derived'  => Ledger::derivedBalance($account),
        ]);
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id|different:to_account_id',
            'to_account_id'   => 'required|exists:accounts,id',
            'amount'          => 'required|numeric|min:1',
        ]);

        $refNo = 'TRF-' . strtoupper(Str::random(8));

        // Ledger::transfer locks both accounts before reading the balance, so two
        // transfers emptying the same account can't both see enough in it.
        $error = Ledger::transfer(
            (int) $request->from_account_id,
            (int) $request->to_account_id,
            (float) $request->amount,
            ['reference' => $refNo, 'description' => $request->notes ?: null]
        );

        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        Payment::create(['reference_no' => $refNo, 'type' => 'transfer', 'account_id' => $request->from_account_id, 'to_account_id' => $request->to_account_id, 'amount' => $request->amount, 'method' => 'bank', 'notes' => $request->notes, 'created_by' => auth()->id()]);

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
            Ledger::credit($account, $amount, [
                'reference'   => $refNo,
                'description' => "Payment for {$sale->invoice_no}",
                'source_type' => 'sale',
                'source_id'   => $sale->id,
            ]);
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
            Ledger::debit($account, $amount, [
                'reference'   => $refNo,
                'description' => "Payment for {$purchase->bill_no}",
                'source_type' => 'purchase',
                'source_id'   => $purchase->id,
            ]);
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
        if ($request->account_id) {
            Ledger::debit((int) $request->account_id, (float) $request->amount, [
                'description' => $request->description,
                'source_type' => 'expense',
                'occurred_at' => $request->expense_date,
            ]);
        }
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
                $delta > 0
                    ? Ledger::debit($e->account_id, $delta, ['description' => "{$r->description} (amount raised)", 'source_type' => 'expense', 'source_id' => $e->id])
                    : Ledger::credit($e->account_id, -$delta, ['description' => "{$r->description} (amount lowered)", 'source_type' => 'expense', 'source_id' => $e->id]);
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
                Ledger::credit($expense->account_id, (float) $expense->amount, [
                    'description' => "Reversed — expense deleted ({$expense->description})",
                    'source_type' => 'expense',
                    'source_id'   => $expense->id,
                ]);
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
        $cashBooks = Account::where('type', 'cash')->where('status', 'active')->orderBy('name')->get();

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
            'settings', 'branches', 'users', 'counters', 'cashBooks', 'assignableRoles', 'apiCredentials', 'apiKeyState'
        ));
    }

    public function save(Request $request)
    {
        // Per-counter cash rules live on the counters table, not in settings.
        foreach ($request->input('counter_float', []) as $counterId => $amount) {
            $notes = collect($request->input("counter_notes.$counterId", []))
                ->map(fn ($n) => max(0, (int) $n))
                ->filter()
                ->all();

            // Through the model, so the retain_notes array cast does the encoding
            // once — a query-builder update would store whatever it was handed.
            \App\Models\Counter::find($counterId)?->update([
                'float_amount'    => max(0, (float) $amount),
                'retain_coins'    => (bool) $request->input("counter_coins.$counterId", 0),
                'retain_notes'    => $notes ?: null,
                'cashier_book_id' => $request->input("counter_book.$counterId") ?: null,
            ]);
        }

        // Only the keys this screen owns. Walking the whole request instead
        // turned any field that reached it into a stored setting — including
        // API-key fields, which must go through the encrypted path.
        Setting::saveGroup('pos', $request);

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
                    Ledger::credit($account, $returnAmount, [
                        'description' => "Refund for {$return->dr_note_no}",
                        'source_type' => 'purchase_return',
                        'source_id'   => $return->id,
                    ]);
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
                    Ledger::debit($p->account_id, (float) $p->amount, [
                        'description' => "Reversed — {$purchaseReturn->dr_note_no} deleted",
                        'source_type' => 'purchase_return',
                        'source_id'   => $purchaseReturn->id,
                    ]);
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
        // Same rule as the main settings screen: only the keys it owns.
        Setting::saveGroup('website', $request);

        return back()->with('success', 'Website settings saved.');
    }
}
