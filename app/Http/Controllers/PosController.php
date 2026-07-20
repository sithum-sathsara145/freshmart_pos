<?php

namespace App\Http\Controllers;

use App\Support\AttendanceRecorder;
use App\Support\CashRetention;
use App\Support\CurrentBranch;
use App\Support\DocumentNumber;
use App\Support\Inventory;
use App\Support\Ledger;
use App\Support\TenderAccount;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Stock;
use App\Models\StockLayer;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Coupon;
use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\HeldBill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    // Sri Lankan rupee denominations (notes then coins)
    private const DENOMINATIONS = [5000, 2000, 1000, 500, 100, 50, 20, 10, 5, 2, 1];

    public function index()
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $branch = auth()->user()->branch;
        $counter = $this->currentCounter();

        $openSession = null;
        $lastClose   = null;
        if ($counter) {
            $openSession = CounterSession::where('counter_id', $counter->id)
                ->where('status', 'open')->latest('opened_at')->first();

            if ($openSession) {
                // live expected cash so far = opening + cash sales since open
                $openSession->cash_sales_so_far = $this->cashSalesSince($counter->id, $openSession->opened_at);
            } else {
                $lastClose = CounterSession::where('counter_id', $counter->id)
                    ->where('status', 'closed')->latest('closed_at')->first();
            }
        }

        // Cash books the cashier can hand the drawer into at the end of a shift.
        // Bank accounts are not offered: this is physical cash changing hands,
        // and it has not been to a bank.
        $depositAccounts = $counter
            ? Account::where('type', 'cash')->where('status', 'active')
                ->where(fn ($q) => $q->whereBranch($counter->branch_id)->orWhereNull('branch_id'))
                ->orderByDesc('is_cashier_book')->orderBy('name')
                ->get(['id', 'name', 'type', 'is_cashier_book'])
            : collect();

        $retention = $counter ? $this->retentionRule($counter) : null;
        $defaultBook = $counter ? $this->cashierBookFor($counter, null) : null;

        // Nothing on the account: offer the free counters instead of a dead end.
        $freeCounters = $counter ? collect() : $this->availableCounters();

        return view('pos.index', compact('categories', 'branch', 'counter', 'openSession', 'lastClose', 'depositAccounts', 'retention', 'defaultBook', 'freeCounters')
            + ['denominations' => self::DENOMINATIONS]);
    }

    // Cash collected at a counter since a given time (what should be in the drawer from sales).
    // Uses cash_amount so split (cash+card) sales contribute only their cash portion.
    private function cashSalesSince(int $counterId, $since): float
    {
        return (float) Sale::where('counter_id', $counterId)
            ->where('created_at', '>=', $since)
            ->sum('cash_amount');
    }

    // Open the counter for the current session with a counted opening float
    public function openCounter(Request $request)
    {
        $counter = $this->currentCounter();

        // Someone with no counter of their own may step up to a free one.
        if (! $counter && $request->filled('counter_id')) {
            $counter = $this->availableCounters()->contains('id', (int) $request->counter_id)
                ? Counter::find($request->counter_id)
                : null;

            if (! $counter) {
                return response()->json([
                    'success' => false,
                    'message' => 'That counter is not free — someone else is on it.',
                ], 422);
            }

            session(['pos_counter_id' => $counter->id]);
        }

        if (! $counter) {
            return response()->json(['success' => false, 'message' => 'Pick a counter to work at first.'], 422);
        }

        if (CounterSession::where('counter_id', $counter->id)->where('status', 'open')->exists()) {
            return response()->json(['success' => false, 'message' => 'Counter is already open.'], 422);
        }

        $denoms  = $this->cleanDenoms($request->input('denoms', []));
        $opening = $this->denomsTotal($denoms);

        DB::transaction(function () use ($counter, $denoms, $opening) {
            CounterSession::create([
                'counter_id'      => $counter->id,
                'branch_id'       => $counter->branch_id,
                'opened_by'       => auth()->id(),
                'opening_balance' => $opening,
                'opening_denoms'  => $denoms,
                'status'          => 'open',
                'opened_at'       => now(),
            ]);
            $counter->update(['status' => 'open', 'cash_balance' => $opening]);
        });

        $this->recordAttendance('in');

        return response()->json(['success' => true, 'opening' => $opening, 'message' => 'Counter opened.']);
    }

    /**
     * The counter this person is working at.
     *
     * Usually the one on their account. Someone with none — an owner or manager
     * covering a till — picks one when they open it, and that choice lives in
     * their login session rather than on the user record, so it lapses when the
     * counter is closed instead of quietly making them a cashier for good.
     */
    private function currentCounter(): ?Counter
    {
        if ($assigned = auth()->user()->counter) {
            return $assigned;
        }

        $picked = session('pos_counter_id');

        return $picked ? Counter::find($picked) : null;
    }

    /** Counters someone with no counter of their own could step up to. */
    private function availableCounters(): \Illuminate\Support\Collection
    {
        $branchId = auth()->user()->branch_id;

        return Counter::whereBranch($branchId)
            ->whereDoesntHave('sessions', fn ($q) => $q->where('status', 'open'))
            ->whereNotIn('id', User::whereNotNull('counter_id')->pluck('counter_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** How much of the drawer this counter's cashier keeps back. */
    private function retentionRule(Counter $counter): array
    {
        return [
            'coins'   => (bool) $counter->retain_coins,
            'notes'   => $counter->retain_notes ?: [],
            'minimum' => (float) $counter->float_amount,
        ];
    }

    /**
     * The cash book the takings go into: the one picked at close, else the
     * counter's own, else the branch's designated cashier book.
     */
    private function cashierBookFor(Counter $counter, $chosenId): ?Account
    {
        $branchBooks = Account::where('type', 'cash')->where('status', 'active')
            ->where(fn ($q) => $q->whereBranch($counter->branch_id)->orWhereNull('branch_id'));

        if ($chosenId && $book = (clone $branchBooks)->find($chosenId)) {
            return $book;
        }

        return (clone $branchBooks)->whereKey($counter->cashier_book_id)->first()
            ?? (clone $branchBooks)->where('is_cashier_book', 1)->orderBy('id')->first()
            ?? (clone $branchBooks)->orderBy('id')->first();
    }

    // Close the counter: reconcile counted cash against opening + cash sales
    public function closeCounter(Request $request)
    {
        $counter = $this->currentCounter();
        $session = $counter
            ? CounterSession::where('counter_id', $counter->id)->where('status', 'open')->latest('opened_at')->first()
            : null;

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'No open counter session to close.'], 422);
        }

        $denoms    = $this->cleanDenoms($request->input('denoms', []));
        $counted   = $this->denomsTotal($denoms);
        $cashSales = $this->cashSalesSince($counter->id, $session->opened_at);
        $expected  = (float) $session->opening_balance + $cashSales;
        $variance  = round($counted - $expected, 2);

        // What the cashier keeps: every coin, the notes the admin set aside, and
        // more notes on top if that does not reach the minimum float.
        $split    = CashRetention::split($denoms, $this->retentionRule($counter));
        $retained = $split['keepTotal'];
        $handIn   = $split['moveTotal'];

        // Closing only counts and sets the money aside. It is not in a cash book
        // until somebody physically takes it there and records that — see
        // CounterSessionController::deposit().
        DB::transaction(function () use (
            $session, $counter, $denoms, $counted, $cashSales, $expected,
            $variance, $retained, $handIn, $split
        ) {
            $session->update([
                'closed_by'          => auth()->id(),
                'closing_denoms'     => $denoms,
                'closing_balance'    => $counted,
                'cash_sales'         => $cashSales,
                'expected_closing'   => $expected,
                'variance'           => $variance,
                'float_retained'     => $retained,
                'retained_denoms'    => $split['keep'],
                'deposit_amount'     => $handIn,
                'deposit_account_id' => null,   // set when it is actually handed in
                'deposited_at'       => null,
                'status'             => 'closed',
                'closed_at'          => now(),
            ]);

            // cash_balance is the float the cashier carries to the next shift.
            $counter->update(['status' => 'closed', 'cash_balance' => $retained]);
        });

        $this->recordAttendance('out');

        // A counter that was stepped up to is handed back, so the next shift
        // picks again rather than inheriting it.
        session()->forget('pos_counter_id');

        return response()->json([
            'success'    => true,
            'opening'    => (float) $session->opening_balance,
            'cash_sales' => $cashSales,
            'expected'   => $expected,
            'counted'    => $counted,
            'variance'   => $variance,
            'float'      => $retained,
            'kept'       => $split['keep'],
            'deposit'    => $handIn,
            'message'    => 'Counter closed.',
        ]);
    }

    /**
     * Mirror a counter open/close onto the staff attendance sheet.
     *
     * Deliberately best-effort and deliberately OUTSIDE the counter-session
     * transaction: a user with no HR record is a normal no-op, and an attendance
     * failure must never roll back a till or stop someone selling. Anything that
     * goes wrong is logged and swallowed.
     */
    private function recordAttendance(string $direction): void
    {
        try {
            $staff = auth()->user()?->staff;

            if (! $staff) {
                return;   // no HR record — nothing to record against
            }

            $direction === 'in'
                ? AttendanceRecorder::clockIn($staff)
                : AttendanceRecorder::clockOut($staff);

        } catch (\Throwable $e) {
            report($e);
        }
    }

    // Keep only valid denomination => quantity pairs
    private function cleanDenoms(array $input): array
    {
        $out = [];
        foreach (self::DENOMINATIONS as $d) {
            $qty = (int) ($input[$d] ?? 0);
            if ($qty > 0) $out[$d] = $qty;
        }
        return $out;
    }

    private function denomsTotal(array $denoms): float
    {
        $total = 0;
        foreach ($denoms as $denom => $qty) {
            $total += (int) $denom * (int) $qty;
        }
        return (float) $total;
    }

    // Ajax: search products for POS screen
    public function searchProducts(Request $request)
    {
        $q = $request->get('q', '');
        $category = $request->get('category');
        $branchId = CurrentBranch::id();

        $products = Product::with(['category', 'brand'])
            ->where('status', 'active')
            ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%$q%")
                      ->orWhere('barcode', 'like', "%$q%")
                      ->orWhere('sku', 'like', "%$q%");
            }))
            ->when($category, fn($query) => $query->where('category_id', $category))
            ->select('id', 'name', 'barcode', 'sale_price', 'tax_percent', 'image', 'category_id', 'unit')
            ->limit(30)
            ->get()
            ->map(function ($product) use ($branchId) {
                $stock = Stock::where('product_id', $product->id)
                              ->whereBranch($branchId)
                              ->value('quantity') ?? 0;
                return [
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'barcode'     => $product->barcode,
                    'price'       => $product->sale_price,
                    'tax_percent' => $product->tax_percent,
                    'unit'        => $product->unit,
                    'stock'       => $stock,
                    'image'       => $product->image ? asset('storage/' . $product->image) : null,
                    'category'    => $product->category?->name,
                ];
            });

        return response()->json($products);
    }

    // Ajax: find product by barcode (scanner)
    public function findByBarcode(string $barcode)
    {
        $branchId = CurrentBranch::id();

        // 1) Exact match first — covers manufacturer barcodes AND our internal/custom
        //    store barcodes. Doing this before scale-parsing means a stored barcode can
        //    never be mis-read as a scale code.
        $product = Product::where('barcode', $barcode)
                          ->where('status', 'active')
                          ->first();

        if ($product) {
            $stock = Stock::where('product_id', $product->id)
                          ->whereBranch($branchId)
                          ->value('quantity') ?? 0;

            $priceOptions = $product->is_weighed ? [] : StockLayer::where('product_id', $product->id)
                ->whereBranch($branchId)
                ->where('qty_remaining', '>', 0)
                ->distinct()
                ->orderBy('sale_price')
                ->pluck('sale_price')
                ->map(fn($v) => (float) $v)
                ->all();

            return response()->json([
                'id'          => $product->id,
                'name'        => $product->name,
                'barcode'     => $product->barcode,
                'price'       => $product->sale_price,
                'price_options' => $priceOptions,
                'is_weighed'  => (bool) $product->is_weighed,
                'tax_percent' => $product->tax_percent,
                'unit'        => $product->unit,
                'stock'       => $stock,
            ]);
        }

        // 2) Scale / weighed embedded barcode (prefix "2")? Resolve by PLU and read the
        //    embedded weight or price. Returns null for ordinary barcodes.
        $scale = \App\Support\ScaleBarcode::parse($barcode);
        if ($scale) {
            $product = Product::where('is_weighed', true)
                              ->whereRaw('CAST(scale_plu AS UNSIGNED) = ?', [(int) $scale['plu']])
                              ->where('status', 'active')
                              ->first();

            if (! $product) {
                return response()->json(['message' => "No weighed product for PLU {$scale['plu']}."], 404);
            }

            $unitPrice = (float) $product->sale_price;
            if ($scale['embed'] === 'weight') {
                $qty = round($scale['value'], 3);                                   // value is the weight
            } else {
                $qty = $unitPrice > 0 ? round($scale['value'] / $unitPrice, 3) : 0; // value is the line price
            }

            $stock = Stock::where('product_id', $product->id)->whereBranch($branchId)->value('quantity') ?? 0;

            return response()->json([
                'id'          => $product->id,
                'name'        => $product->name,
                'barcode'     => $product->barcode,
                'price'       => $unitPrice,
                'tax_percent' => $product->tax_percent,
                'unit'        => $product->unit,
                'stock'       => $stock,
                'weighed'     => true,
                'qty'         => $qty,
            ]);
        }

        // 3) Nothing matched.
        return response()->json(['message' => 'No product found.'], 404);
    }

    // Process sale from POS
    public function storeSale(Request $request)
    {
        $request->validate([
            'items'          => 'required|array|min:1',
            'items.*.id'     => 'nullable|integer|exists:products,id',   // null = custom item
            'items.*.name'   => 'nullable|string|max:255',
            'items.*.qty'    => 'required|numeric|min:0.001',
            'items.*.price'  => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,credit,mixed',
            'paid_amount'    => 'required|numeric|min:0',
            'discount_amount'=> 'nullable|numeric|min:0',
            'tax_amount'     => 'nullable|numeric|min:0',
            'card_last4'     => 'nullable|digits:4',
            'cash_amount'    => 'nullable|numeric|min:0',
            'card_amount'    => 'nullable|numeric|min:0',
            'credit_amount'  => 'nullable|numeric|min:0',
        ]);

        // A sale belongs to exactly one branch, so All-branches mode can't ring one up.
        if (! $branchId = CurrentBranch::requireId()) {
            return response()->json([
                'success' => false,
                'message' => 'Pick a working branch before making sales.',
            ], 422);
        }

        // Every sale happens at an open counter. This used to be skipped entirely
        // for anyone without a counter, so they could bill with no session at all
        // — and the cash portion of those sales was tracked nowhere, since it
        // stays with the counter until close and never touches a cash book.
        $counterId = $this->currentCounter()?->id;

        if (! $counterId) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not linked to a counter, so it cannot take payments. An admin can assign one under Settings → Users.',
            ], 422);
        }

        if (! CounterSession::where('counter_id', $counterId)->where('status', 'open')->exists()) {
            return response()->json(['success' => false, 'message' => 'Open the counter before making sales.'], 422);
        }

        // Total requested per product, across however many lines it appears on.
        $needByProduct = [];
        foreach ($request->items as $item) {
            if (! empty($item['id'])) {
                $needByProduct[$item['id']] = ($needByProduct[$item['id']] ?? 0) + (float) $item['qty'];
            }
        }

        DB::beginTransaction();

        try {
            // Inside the transaction, and holding a lock on each product's row:
            // checking beforehand let two tills selling the last unit both pass
            // and then both consume it.
            if ($shortage = Inventory::guard($needByProduct, $branchId)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => $shortage], 422);
            }

            $branchId  = CurrentBranch::id();
            $counterId = $this->currentCounter()?->id;
            $userId    = auth()->id();

            // Validate coupon if provided
            $coupon = null;
            $discountAmount = 0;
            if ($request->coupon_code) {
                $coupon = Coupon::where('code', $request->coupon_code)
                                ->where('status', 'active')
                                ->whereDate('expires_at', '>=', today())
                                ->first();

                if ($coupon && ($coupon->max_uses === null || $coupon->used_count < $coupon->max_uses)) {
                    if ($coupon->type === 'percentage') {
                        $discountAmount = round($request->subtotal * $coupon->value / 100, 2);
                    } else {
                        $discountAmount = min($coupon->value, $request->subtotal);
                    }
                }
            }

            // Calculate totals
            $subtotal   = collect($request->items)->sum(fn($item) => $item['price'] * $item['qty']);
            $taxAmount  = collect($request->items)->sum(function ($item) {
                return ($item['price'] * $item['qty']) * ($item['tax_percent'] ?? 0) / 100;
            });
            // Manual discount / tax entered at the POS take precedence
            if ($request->filled('discount_amount')) {
                $discountAmount = max(0, (float) $request->discount_amount);
            }
            if ($request->filled('tax_amount')) {
                $taxAmount = max(0, (float) $request->tax_amount);
            }

            $total  = max(0, $subtotal - $discountAmount + $taxAmount);
            $method = $request->payment_method;

            // Tender breakdown across cash / card / credit. The credit portion is
            // placed on the customer's account (the unpaid remainder) — it is NOT a
            // payment, so it never gets a payments row; it surfaces as balanceDue().
            if ($method === 'mixed') {
                $creditPortion = max(0, min((float) $request->credit_amount, $total));
                $cardPortion   = max(0, min((float) $request->card_amount, $total - $creditPortion));
                $cashNeeded    = round($total - $cardPortion - $creditPortion, 2); // cash to complete the paid part
                $cashGiven     = (float) $request->cash_amount;
                $paidAmount    = round($total - $creditPortion, 2);                // paid now (cash + card)
                $change        = max(0, round($cashGiven - $cashNeeded, 2));        // change out of the cash given
                $cashInDrawer  = $cashNeeded;                                       // net cash retained
            } elseif ($method === 'credit') {
                $creditPortion = $total;                                            // whole bill on account
                $cardPortion   = 0;
                $paidAmount    = 0;
                $change        = 0;
                $cashInDrawer  = 0;
            } else {
                $creditPortion = 0;
                $paidAmount    = min($request->paid_amount, $total);
                $change        = max(0, $request->paid_amount - $total);
                $cardPortion   = $method === 'card' ? $paidAmount : 0;
                $cashInDrawer  = $method === 'cash' ? $paidAmount : 0;
            }

            // Credit guard: only registered, approved customers (or any registered
            // customer when the store allows credit for new ones) may take credit,
            // never walk-in — and only within their credit limit.
            if ($creditPortion > 0) {
                $creditCustomer = $request->customer_id ? Customer::find($request->customer_id) : null;
                if (! $creditCustomer) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Select a registered customer for credit — walk-in customers can't buy on credit."], 422);
                }
                $allowNew = filter_var(\App\Models\Setting::get('allow_credit_new_customers'), FILTER_VALIDATE_BOOLEAN);
                if (! $creditCustomer->credit_approved && ! $allowNew) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'This customer is not approved for credit.'], 422);
                }
                if (blank($creditCustomer->nic)) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Add the customer's NIC before selling on credit."], 422);
                }
                if ($creditCustomer->credit_limit !== null) {
                    $projected = $creditCustomer->outstandingBalance() + $creditPortion;
                    if ($projected > (float) $creditCustomer->credit_limit + 1e-9) {
                        $over = round($projected - (float) $creditCustomer->credit_limit, 2);
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => "Over the customer's credit limit by Rs. " . number_format($over, 2) . '.'], 422);
                    }
                }
            }

            // Create sale
            $sale = Sale::create([
                'invoice_no'     => DocumentNumber::next('invoice'),
                'customer_id'    => $request->customer_id,
                'branch_id'      => $branchId,
                'counter_id'     => $counterId,
                'user_id'        => $userId,
                'coupon_id'      => $coupon?->id,
                'subtotal'       => $subtotal,
                'discount_amount'=> $discountAmount,
                'tax_amount'     => $taxAmount,
                'total'          => $total,
                'paid_amount'    => $paidAmount,
                'cash_amount'    => $cashInDrawer,
                'change_amount'  => $change,
                'credit_amount'  => $creditPortion,
                'payment_method' => $method,
                'status'         => $paidAmount >= $total ? 'paid' : 'partial',
                'notes'          => $request->notes,
            ]);

            // Sale items + stock deduction (consume FIFO/WAC cost layers for COGS)
            foreach ($request->items as $item) {
                $qty = (float) $item['qty'];

                // Custom / one-off item — no catalogue product, no stock movement.
                if (empty($item['id'])) {
                    $name = trim((string) ($item['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    SaleItem::create([
                        'sale_id'     => $sale->id,
                        'product_id'  => null,
                        'name'        => $name,
                        'quantity'    => $qty,
                        'unit_price'  => $item['price'],
                        'cost'        => 0,
                        'tax_percent' => $item['tax_percent'] ?? 0,
                        'subtotal'    => $item['price'] * $qty,
                    ]);
                    continue;
                }

                $product = Product::find($item['id']);
                $cogs    = $product
                    ? Inventory::consume($product, $branchId, $qty, isset($item['price']) ? (float) $item['price'] : null)
                    : 0;

                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $item['id'],
                    'quantity'     => $qty,
                    'unit_price'   => $item['price'],
                    'cost'         => $cogs,
                    'tax_percent'  => $item['tax_percent'] ?? 0,
                    'subtotal'     => $item['price'] * $qty,
                ]);
            }

            // Update coupon usage
            if ($coupon) {
                $coupon->increment('used_count');
            }

            // Add loyalty points (1 point per Rs. 20)
            if ($request->customer_id) {
                $points = (int) ($total / 20);
                Customer::find($request->customer_id)->increment('loyalty_points', $points);
                Customer::find($request->customer_id)->increment('total_purchases', $total);
            }

            // Payment record(s) — one per tender (split sales create a cash and a card record).
            // Each tender is banked where it actually goes: cash to the till's cash
            // account, card to the bank. Posting the lot to the cash account made it
            // read high by every card sale and left the bank never seeing the money.
            if ($paidAmount > 0) {
                $tenders = [];
                if ($method === 'mixed') {
                    if ($cashInDrawer > 0) $tenders[] = ['method' => 'cash', 'amount' => $cashInDrawer];
                    if ($cardPortion > 0)  $tenders[] = ['method' => 'card', 'amount' => $cardPortion];
                } else {
                    $m = in_array($method, ['cash', 'card', 'bank', 'cheque'], true) ? $method : 'cash';
                    $tenders[] = ['method' => $m, 'amount' => $paidAmount];
                }

                foreach ($tenders as $t) {
                    $account = TenderAccount::for($branchId, $t['method']);
                    if (! $account) {
                        continue;
                    }

                    $reference = ($t['method'] === 'card' && $request->filled('card_last4'))
                        ? 'CARD-' . $request->card_last4 . '-' . $sale->id
                        : 'PAY-' . strtoupper(Str::random(8));

                    Payment::create([
                        'reference_no' => $reference,
                        'type'         => 'payment_in',
                        'account_id'   => $account->id,
                        'party_type'   => 'customer',
                        'party_id'     => $request->customer_id,
                        'sale_id'      => $sale->id,
                        'amount'       => $t['amount'],
                        'method'       => $t['method'],
                        'created_by'   => $userId,
                    ]);

                    // Cash stays with the cashier until the counter is closed and
                    // the drawer is counted, so it is not in any cash book yet —
                    // it is tracked on the counter and handed in at close.
                    // Card and bank money really has left for the bank, so that
                    // lands in the account straight away.
                    if ($t['method'] !== 'cash') {
                        Ledger::credit($account, $t['amount'], [
                            'reference'   => $reference,
                            'description' => "Sale {$sale->invoice_no}",
                            'source_type' => 'sale',
                            'source_id'   => $sale->id,
                        ]);
                    }
                }
            }

            // The cash portion sits with the counter until it is closed and counted.
            // A counter is guaranteed by the guard above, so this is where every
            // rupee of cash taken at the till is accounted for.
            if ($cashInDrawer > 0) {
                \App\Models\Counter::find($counterId)->increment('cash_balance', $cashInDrawer);
            }

            DB::commit();

            return response()->json([
                'success'     => true,
                'sale_id'     => $sale->id,
                'invoice_no'  => $sale->invoice_no,
                'total'       => $total,
                'change'      => $change,
                'cash_amount' => $cashInDrawer,
                'balance_due' => round($creditPortion, 2),
                'is_credit'   => $creditPortion > 0,
                'message'     => 'Sale completed successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Sale failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function receipt(int $id)
    {
        $sale = Sale::with(['items.product', 'customer', 'branch', 'user'])
                    ->findOrFail($id);

        $settings = \App\Models\Setting::pluck('value', 'key_name');

        return view('pos.receipt', compact('sale', 'settings'));
    }

    // ── Held / parked bills ─────────────────────────────────────────────
    public function holdBill(Request $request)
    {
        $request->validate([
            'payload'    => 'required|array',
            'label'      => 'nullable|string|max:255',
            'item_count' => 'nullable|integer|min:0',
            'total'      => 'nullable|numeric|min:0',
        ]);

        if (! $branchId = CurrentBranch::requireId()) {
            return response()->json(['success' => false, 'message' => CurrentBranch::pickBranchMessage()], 422);
        }

        $bill = HeldBill::create([
            'branch_id'  => $branchId,
            'user_id'    => auth()->id(),
            'label'      => $request->label,
            'item_count' => $request->item_count ?? count($request->input('payload.cart', [])),
            'total'      => $request->total ?? 0,
            'payload'    => $request->payload,
        ]);

        return response()->json(['success' => true, 'id' => $bill->id]);
    }

    public function heldBills()
    {
        $bills = HeldBill::whereBranch(CurrentBranch::id())
            ->latest()
            ->get(['id', 'label', 'item_count', 'total', 'created_at']);

        return response()->json($bills);
    }

    public function resumeHeld($id)
    {
        $bill = HeldBill::whereBranch(CurrentBranch::id())->findOrFail($id);
        $payload = $bill->payload;
        $bill->delete();   // removed once it's back in the cart

        return response()->json(['success' => true, 'payload' => $payload]);
    }

    public function discardHeld($id)
    {
        HeldBill::whereBranch(CurrentBranch::id())->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }


}
