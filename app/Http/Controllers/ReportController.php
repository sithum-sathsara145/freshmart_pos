<?php

namespace App\Http\Controllers;

use App\Support\CurrentBranch;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Payment;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Support\ReportRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Reports landing hub — GA-style overview: a KPI row + net-sales trend for the
     * selected period, then a grid of links to every report.
     */
    public function index(Request $request)
    {
        $range    = ReportRange::fromRequest($request);
        $branchId = CurrentBranch::id();

        // Headline metrics for an arbitrary window (net of returns).
        $metrics = function (string $from, string $to) use ($branchId) {
            $gross   = (float) Sale::whereBranch($branchId)->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->sum('total');
            $ret     = $this->returnTotals($branchId, $from, $to);
            $cogs    = (float) SaleItem::whereHas('sale', fn($q) => $q->whereBranch($branchId)
                        ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))->sum('cost') - $ret['cogs'];
            return [
                'net'       => $gross - $ret['amount'],
                'invoices'  => (int) Sale::whereBranch($branchId)->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->count(),
                'profit'    => ($gross - $ret['amount']) - $cogs,
                'purchases' => (float) Purchase::whereBranch($branchId)->whereBetween('purchase_date', [$from, $to])->sum('total'),
                'expenses'  => (float) Expense::whereBranch($branchId)->whereBetween('expense_date', [$from, $to])->sum('amount'),
            ];
        };

        $cur  = $metrics($range->fromDate(), $range->toDate());
        $prev = $range->compare ? $metrics($range->prevFromDate(), $range->prevToDate()) : null;
        $d    = fn($k) => $prev ? ReportRange::delta($cur[$k], $prev[$k]) : null;

        $kpis = [
            ['label' => 'Net sales',    'value' => 'Rs. ' . number_format($cur['net']),       'delta' => $d('net'),       'color' => '#4ade80'],
            ['label' => 'Invoices',     'value' => number_format($cur['invoices']),           'delta' => $d('invoices')],
            ['label' => 'Gross profit', 'value' => 'Rs. ' . number_format($cur['profit']),    'delta' => $d('profit'),    'color' => '#a5b4fc'],
            ['label' => 'Purchases',    'value' => 'Rs. ' . number_format($cur['purchases']), 'delta' => $d('purchases'), 'invert' => true],
            ['label' => 'Expenses',     'value' => 'Rs. ' . number_format($cur['expenses']),  'delta' => $d('expenses'),  'invert' => true, 'color' => '#fb923c'],
        ];

        // Net-sales trend (gross per bucket minus returns per bucket).
        $netSeries = function (string $from, string $to, ?array $keys) use ($branchId, $range) {
            $sales = Sale::whereBranch($branchId)->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->get(['created_at', 'total']);
            $rets  = SaleReturn::whereHas('sale', fn($q) => $q->whereBranch($branchId))
                        ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->get(['created_at', 'return_amount']);
            $g = $range->series($range->bucketMap($sales, 'created_at', 'total'), $keys);
            $r = $range->series($range->bucketMap($rets, 'created_at', 'return_amount'), $keys);
            return array_map(fn($a, $b) => round($a - $b, 2), $g, $r);
        };

        $series = [['name' => 'Net sales', 'data' => $netSeries($range->fromDate(), $range->toDate(), null), 'color' => '#4ade80']];
        if ($range->compare) {
            $series[] = ['name' => 'Previous', 'data' => $netSeries($range->prevFromDate(), $range->prevToDate(), $range->prevBucketKeys()), 'color' => '#4ade80', 'dashed' => true];
        }
        $trend = ['type' => 'line', 'labels' => $range->bucketLabels(), 'series' => $series, 'money' => true];

        // Report cards. `route` = null means "coming in a later phase".
        $q = $range->query();
        $cards = [
            ['title' => 'Invoices',         'desc' => 'Every invoice, daily totals, credit notes', 'icon' => 'ti-file-invoice', 'color' => '#4ade80', 'url' => route('reports.invoices', $q)],
            ['title' => 'Sales',            'desc' => 'Invoices, baskets, peak hours',       'icon' => 'ti-shopping-cart',   'color' => '#4ade80', 'url' => route('reports.sales_summary', $q)],
            ['title' => 'Revenue & Profit', 'desc' => 'Revenue, COGS, margin, net profit',   'icon' => 'ti-trending-up',     'color' => '#a5b4fc', 'url' => route('reports.profit_loss', $q)],
            ['title' => 'Product sales',    'desc' => 'Best sellers and profit per product', 'icon' => 'ti-package',         'color' => '#60a5fa', 'url' => route('reports.product_sales', $q)],
            ['title' => 'Gross profit',     'desc' => 'By item, category, supplier, location or invoice', 'icon' => 'ti-trending-up', 'color' => '#a5b4fc', 'url' => route('reports.gross_profit', $q)],
            ['title' => 'Net profit',       'desc' => 'Day by day, after cost and expenses', 'icon' => 'ti-report-money',    'color' => '#4ade80', 'url' => route('reports.net_profit', $q)],
            ['title' => 'Refunds',          'desc' => 'What came back, and the margin with it', 'icon' => 'ti-arrow-back-up', 'color' => '#f87171', 'url' => route('reports.refunds', $q)],
            ['title' => 'Purchases',        'desc' => 'Goods received, returns, payables',   'icon' => 'ti-truck-delivery',  'color' => '#fbbf24', 'url' => auth()->user()->can('purchases.view') ? route('reports.purchases', $q) : null],
            ['title' => 'Stock movement',   'desc' => 'In / out, write-offs, stock value',   'icon' => 'ti-transfer',        'color' => '#2dd4bf', 'url' => null],
            ['title' => 'Stock summary',    'desc' => 'On-hand value by product',            'icon' => 'ti-boxes',           'color' => '#60a5fa', 'url' => route('reports.stock_summary', $q)],
            ['title' => 'Low stock alerts', 'desc' => 'Items at or below reorder level',     'icon' => 'ti-alert-triangle',  'color' => '#f87171', 'url' => route('reports.stock_alert', $q)],
            ['title' => 'Cash book',        'desc' => 'Ledger, account balances, hand-overs', 'icon' => 'ti-book-2',         'color' => '#2dd4bf', 'url' => route('reports.cash_book', $q)],
            ['title' => 'Cashier-wise',     'desc' => 'Takings by cashier, customer or item', 'icon' => 'ti-user-dollar',    'color' => '#818cf8', 'url' => route('reports.cashiers', $q)],
            ['title' => 'Counter sessions', 'desc' => 'Cash variance by till and cashier',   'icon' => 'ti-cash-register',   'color' => '#c084fc', 'url' => null],
            ['title' => 'Payments & cash',  'desc' => 'Money in vs out, by method',          'icon' => 'ti-cash',            'color' => '#4ade80', 'url' => route('reports.payments', $q)],
            ['title' => 'Expenses',         'desc' => 'Spending by category',                'icon' => 'ti-credit-card',     'color' => '#fb923c', 'url' => route('reports.expenses', $q)],
            ['title' => 'Rate list',        'desc' => 'Current price list',                  'icon' => 'ti-list-numbers',    'color' => '#94a3b8', 'url' => route('reports.rate_list', $q)],
            ['title' => 'Staff activity',   'desc' => 'Sales by cashier',                    'icon' => 'ti-users',           'color' => '#818cf8', 'url' => route('reports.user_reports', $q)],
        ];

        // HRM reports are only listed for people who can actually open them, so
        // nobody is shown a card that 403s.
        $user = auth()->user();

        if ($user->can('hrm.view')) {
            $cards[] = ['title' => 'Attendance summary', 'desc' => 'Days, hours and overtime per staff', 'icon' => 'ti-calendar-check', 'color' => '#2dd4bf', 'url' => route('reports.hrm_attendance', $q)];
            $cards[] = ['title' => 'Leave summary',      'desc' => 'Entitled, used and remaining',       'icon' => 'ti-beach',          'color' => '#fb923c', 'url' => route('reports.hrm_leave', $q)];
        }

        if ($user->can('hrm.payroll.manage')) {
            $cards[] = ['title' => 'Payroll register', 'desc' => 'Monthly salary sheet with EPF/ETF', 'icon' => 'ti-report-money', 'color' => '#4ade80', 'url' => route('reports.hrm_payroll', $q)];
        }

        return view('reports.index', compact('range', 'kpis', 'trend', 'cards'));
    }
    private function dateRange(Request $request): array
    {
        $from = $request->from_date ?? now()->startOfMonth()->toDateString();
        $to   = $request->to_date   ?? now()->toDateString();
        return [$from, $to];
    }

    /**
     * Returns recorded in the period for this branch. Sales stay immutable, so reports
     * count gross sales and subtract these. Netted by the credit note's own date.
     */
    private function returnTotals(?int $branchId, string $from, string $to): array
    {
        $amount = SaleReturn::whereHas('sale', fn($q) => $q->whereBranch($branchId))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum('return_amount');

        $cogs = SaleReturnItem::whereHas('saleReturn', fn($q) => $q
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->whereHas('sale', fn($s) => $s->whereBranch($branchId)))
            ->sum('cost');

        return ['amount' => (float) $amount, 'cogs' => (float) $cogs];
    }

    /** Per-product returned qty / revenue / cogs in the period (product_id keyed). */
    private function returnsByProduct(?int $branchId, string $from, string $to)
    {
        return SaleReturnItem::selectRaw('product_id, SUM(quantity) as qty, SUM(subtotal) as revenue, SUM(cost) as cogs')
            ->whereHas('saleReturn', fn($q) => $q
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->whereHas('sale', fn($s) => $s->whereBranch($branchId)))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');
    }

    // Profit & Loss
    public function profitLoss(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId    = CurrentBranch::id();

        // Sales are immutable; count gross then net out returns recorded in the period.
        $returns = $this->returnTotals($branchId, $from, $to);

        $salesRevenue = Sale::whereBranch($branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum('total') - $returns['amount'];

        $purchaseCost = Purchase::whereBranch($branchId)
            ->whereBetween('purchase_date', [$from, $to])
            ->sum('total');

        $totalExpenses = Expense::whereBranch($branchId)
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        $totalDiscounts = Sale::whereBranch($branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum('discount_amount');

        // True cost of goods sold for the period (captured per sale line at sale time),
        // less the COGS reversed by returns.
        $cogs = SaleItem::whereHas('sale', fn($q) => $q->whereBranch($branchId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
            ->sum('cost') - $returns['cogs'];

        $grossProfit = $salesRevenue - $cogs;
        $netProfit   = $grossProfit - $totalExpenses;

        // Daily chart data
        $chartData = Sale::whereBranch($branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top products (gross sales, netted per-product by returns in the period)
        $retByProduct = $this->returnsByProduct($branchId, $from, $to);
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(quantity) as qty, SUM(subtotal) as revenue, SUM(cost) as cogs'))
            ->whereHas('sale', fn($q) => $q->whereBranch($branchId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(function ($item) use ($retByProduct) {
                $r       = $retByProduct->get($item->product_id);
                $qty     = (float) $item->qty     - (float) ($r->qty ?? 0);
                $revenue = (float) $item->revenue - (float) ($r->revenue ?? 0);
                $cogs    = (float) $item->cogs    - (float) ($r->cogs ?? 0);
                return [
                    'name'    => $item->product?->name,
                    'qty'     => $qty,
                    'revenue' => $revenue,
                    'profit'  => $revenue - $cogs,
                ];
            });

        return view('reports.profit_loss', compact(
            'salesRevenue', 'purchaseCost', 'totalExpenses',
            'totalDiscounts', 'grossProfit', 'netProfit',
            'chartData', 'topProducts', 'from', 'to'
        ));
    }

    // Sales Summary
    public function salesSummary(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId    = CurrentBranch::id();

        $sales = Sale::with(['customer', 'user'])
            ->whereBranch($branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->paginate(20);

        $totals = Sale::whereBranch($branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('COUNT(*) as count, SUM(total) as total, SUM(paid_amount) as paid, SUM(discount_amount) as discount')
            ->first();

        // Sales stay immutable; surface returns and the net separately.
        $returnAmount = $this->returnTotals($branchId, $from, $to)['amount'];
        $netTotal     = (float) ($totals->total ?? 0) - $returnAmount;

        $byPaymentMethod = Sale::whereBranch($branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get();

        // Net each payment-method bucket by returns against sales paid that way.
        $returnByMethod = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->where('sales.branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(sale_returns.created_at)'), [$from, $to])
            ->selectRaw('sales.payment_method as pm, SUM(sale_returns.return_amount) as amt')
            ->groupBy('sales.payment_method')
            ->pluck('amt', 'pm');
        $byPaymentMethod->each(fn($r) => $r->total = (float) $r->total - (float) ($returnByMethod[$r->payment_method] ?? 0));

        $byCategory = SaleItem::select('products.category_id', DB::raw('SUM(sale_items.subtotal) as revenue'))
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereHas('sale', fn($q) => $q->whereBranch($branchId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
            ->with('product.category')
            ->groupBy('products.category_id')
            ->orderByDesc('revenue')
            ->get();

        // Net each category by returned revenue in the period.
        $returnByCategory = SaleReturnItem::join('products', 'sale_return_items.product_id', '=', 'products.id')
            ->whereHas('saleReturn', fn($q) => $q
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->whereHas('sale', fn($s) => $s->whereBranch($branchId)))
            ->selectRaw('products.category_id as cid, SUM(sale_return_items.subtotal) as revenue')
            ->groupBy('products.category_id')
            ->pluck('revenue', 'cid');
        $byCategory->each(fn($r) => $r->revenue = (float) $r->revenue - (float) ($returnByCategory[$r->category_id] ?? 0));

        return view('reports.sales_summary', compact(
            'sales', 'totals', 'returnAmount', 'netTotal', 'byPaymentMethod', 'byCategory', 'from', 'to'
        ));
    }

    // Stock Summary
    public function stockSummary(Request $request)
    {
        $branchId = CurrentBranch::id();

        $stocks = Stock::with(['product.category', 'product.brand'])
            ->whereBranch($branchId)
            ->when($request->category_id, fn($q) => $q->whereHas('product', fn($q) => $q->where('category_id', $request->category_id)))
            ->get()
            ->map(function ($s) {
                return [
                    'product'   => $s->product->name,
                    'category'  => $s->product->category?->name,
                    'brand'     => $s->product->brand?->name,
                    'unit'      => $s->product->unit,
                    'buy_price' => $s->product->purchase_price,
                    'quantity'  => $s->quantity,
                    'value'     => $s->quantity * $s->product->purchase_price,
                    'min_stock' => $s->product->min_stock,
                    'status'    => $s->quantity <= 0 ? 'out' : ($s->quantity < $s->product->min_stock ? 'low' : 'ok'),
                ];
            });

        $totals = [
            'products'   => $stocks->count(),
            'total_value'=> $stocks->sum('value'),
            'low'        => $stocks->where('status', 'low')->count(),
            'out'        => $stocks->where('status', 'out')->count(),
        ];

        return view('reports.stock_summary', compact('stocks', 'totals'));
    }

    // Stock Alert
    public function stockAlert(Request $request)
    {
        $branchId = CurrentBranch::id();

        $alerts = Product::with(['category', 'stocks' => fn($q) => $q->whereBranch($branchId)])
            ->whereHas('stocks', fn($q) => $q->whereBranch($branchId)
                ->whereRaw('quantity < products.min_stock'))
            ->get()
            ->map(function ($p) use ($branchId) {
                $stock = $p->stocks->first();
                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'category'      => $p->category?->name,
                    'current_stock' => $stock?->quantity ?? 0,
                    'min_stock'     => $p->min_stock,
                    'status'        => ($stock?->quantity ?? 0) <= 0 ? 'out' : 'low',
                ];
            });

        return view('reports.stock_alert', compact('alerts'));
    }

    // Rate List
    public function rateList(Request $request)
    {
        $products = Product::with(['category', 'brand'])
            ->where('status', 'active')
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'name'           => $p->name,
                'category'       => $p->category?->name,
                'brand'          => $p->brand?->name,
                'unit'           => $p->unit,
                'purchase_price' => $p->purchase_price,
                'sale_price'     => $p->sale_price,
                'margin'         => $p->profitMargin(),
            ]);

        return view('reports.rate_list', compact('products'));
    }

    // Product Sales Summary
    public function productSales(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId    = CurrentBranch::id();

        $retByProduct = $this->returnsByProduct($branchId, $from, $to);

        $products = SaleItem::select(
                'product_id',
                DB::raw('SUM(quantity) as qty_sold'),
                DB::raw('SUM(subtotal) as revenue'),
                DB::raw('SUM(cost) as cost')
            )
            ->whereHas('sale', fn($q) => $q->whereBranch($branchId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
            ->with(['product:id,name,category_id', 'product.category:id,name'])
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->paginate(20);

        // Net each row by returns in the period, and populate the columns the view needs
        // (product name / category / cost / profit weren't provided before).
        $products->getCollection()->transform(function ($p) use ($retByProduct) {
            $r = $retByProduct->get($p->product_id);
            $p->qty_sold     = (float) $p->qty_sold - (float) ($r->qty ?? 0);
            $p->revenue      = (float) $p->revenue  - (float) ($r->revenue ?? 0);
            $p->cost         = (float) $p->cost     - (float) ($r->cogs ?? 0);
            $p->product_name = $p->product?->name;
            $p->category     = $p->product?->category?->name;
            $p->profit       = $p->revenue - $p->cost;
            return $p;
        });

        $categories = \App\Models\Category::orderBy('name')->get();

        return view('reports.product_sales', compact('products', 'categories', 'from', 'to'));
    }

    // Payments report
    public function payments(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId    = CurrentBranch::id();

        $payments = Payment::with(['account', 'sale', 'purchase'])
            ->whereHas('account', fn($q) => $q->whereBranch($branchId))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(20);

        $totals = [
            'in'  => Payment::whereHas('account', fn($q) => $q->whereBranch($branchId))->where('type', 'payment_in')->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->sum('amount'),
            'out' => Payment::whereHas('account', fn($q) => $q->whereBranch($branchId))->where('type', 'payment_out')->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->sum('amount'),
        ];

        return view('reports.payments', compact('payments', 'totals', 'from', 'to'));
    }

    // Expense Report
    public function expenses(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId    = CurrentBranch::id();

        // The screen has always offered a category filter; it was never applied and
        // the dropdown was never given anything to list.
        $categoryId = $request->category_id ?: null;
        $scoped     = fn () => Expense::whereBranch($branchId)
            ->whereBetween('expense_date', [$from, $to])
            ->when($categoryId, fn ($q, $v) => $q->where('expense_category_id', $v));

        $expenses = $scoped()->with(['category', 'account'])
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        $byCategory = $scoped()
            ->selectRaw('expense_category_id, SUM(amount) as total, COUNT(*) as entries')
            ->with('category')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->get();

        $categories = \App\Models\ExpenseCategory::orderBy('name')->get(['id', 'name']);

        // The menu asks for spending by code and by date as separate reports; both
        // are this same set of expenses, totalled a different way.
        $byDate = $scoped()
            ->selectRaw('expense_date, SUM(amount) as total, COUNT(*) as entries')
            ->groupBy('expense_date')
            ->orderBy('expense_date')
            ->get();

        $mode  = in_array($request->mode, ['category', 'date'], true) ? $request->mode : 'details';
        $total = (float) $scoped()->sum('amount');

        return view('reports.expenses', compact('expenses', 'byCategory', 'byDate', 'categories', 'mode', 'total', 'from', 'to'));
    }

    // User / Cashier Reports
    public function userReports(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId    = CurrentBranch::id();

        $cashiers = Sale::whereBranch($branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('user_id, COUNT(*) as sale_count, SUM(total) as total, SUM(paid_amount) as collected')
            ->with('user:id,name')
            ->groupBy('user_id')
            ->get();

        return view('reports.user_reports', compact('cashiers', 'from', 'to'));
    }

    // ── Transaction reports ───────────────────────────────────────────────

    /**
     * Invoices for a period, as a list, a per-day summary, or the credit notes
     * raised against them.
     *
     * One screen rather than four: the menu lists "details", "summary", "cancel
     * details" and "cancel summary" separately, but they are the same query
     * presented differently, and splitting them would mean four places to keep
     * the filters and totals agreeing.
     */
    public function invoices(Request $request)
    {
        $range    = ReportRange::fromRequest($request);
        $branchId = CurrentBranch::id();
        $mode     = in_array($request->mode, ['summary', 'cancelled'], true) ? $request->mode : 'details';

        $filters = [
            'user_id'        => $request->user_id ?: null,
            'counter_id'     => $request->counter_id ?: null,
            'payment_method' => $request->payment_method ?: null,
            'status'         => $request->status ?: null,
        ];

        $rows    = collect();
        $totals  = ['count' => 0, 'gross' => 0.0, 'discount' => 0.0, 'tax' => 0.0, 'net' => 0.0, 'paid' => 0.0, 'due' => 0.0];

        if ($mode === 'cancelled') {
            // Voided sales are removed outright, so the only durable record of
            // money going back is a credit note. That is what this lists.
            $returns = SaleReturn::with(['sale.customer', 'sale.user', 'createdBy'])
                ->whereHas('sale', fn ($q) => $this->invoiceScope($q, $branchId, $range, $filters))
                ->whereBetween(DB::raw('DATE(created_at)'), [$range->fromDate(), $range->toDate()])
                ->latest()
                ->get();

            $rows = $returns;
            $totals['count'] = $returns->count();
            $totals['net']   = (float) $returns->sum('return_amount');
        } else {
            $sales = $this->invoiceScope(Sale::query(), $branchId, $range, $filters)
                ->with(['customer', 'user', 'counter'])
                ->orderBy('created_at')
                ->get();

            $totals = [
                'count'    => $sales->count(),
                'gross'    => (float) $sales->sum('subtotal'),
                'discount' => (float) $sales->sum(fn ($s) => (float) $s->discount_amount + (float) $s->coupon_discount),
                'tax'      => (float) $sales->sum('tax_amount'),
                'net'      => (float) $sales->sum('total'),
                'paid'     => (float) $sales->sum('paid_amount'),
                'due'      => (float) $sales->sum(fn ($s) => max(0, (float) $s->total - (float) $s->paid_amount)),
            ];

            $rows = $mode === 'summary'
                ? $sales->groupBy(fn ($s) => $s->created_at->toDateString())
                    ->map(fn ($day, $date) => [
                        'date'     => $date,
                        'count'    => $day->count(),
                        'gross'    => (float) $day->sum('subtotal'),
                        'discount' => (float) $day->sum(fn ($s) => (float) $s->discount_amount + (float) $s->coupon_discount),
                        'tax'      => (float) $day->sum('tax_amount'),
                        'net'      => (float) $day->sum('total'),
                        'paid'     => (float) $day->sum('paid_amount'),
                    ])->values()
                : $sales;
        }

        return view('reports.invoices', [
            'range'    => $range,
            'mode'     => $mode,
            'rows'     => $rows,
            'totals'   => $totals,
            'filters'  => $filters,
            'cashiers' => $this->cashierOptions($branchId),
            'counters' => \App\Models\Counter::whereBranch($branchId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * What each cashier took, optionally broken down by the customers they
     * served or the items they sold.
     *
     * The menu asks for three separate reports — sales summary, customer invoice
     * summary, item sales summary — but they differ only in what the totals are
     * grouped by underneath the cashier, so they are one screen with a switch.
     */
    public function cashiers(Request $request)
    {
        $range    = ReportRange::fromRequest($request);
        $branchId = CurrentBranch::id();
        $by       = in_array($request->by, ['customer', 'item'], true) ? $request->by : 'cashier';
        $filters  = ['user_id' => $request->user_id ?: null, 'counter_id' => $request->counter_id ?: null];

        $sales = $this->invoiceScope(Sale::query(), $branchId, $range, $filters)
            ->with(['user', 'customer'])->get();

        $totals = [
            'invoices' => $sales->count(),
            'net'      => (float) $sales->sum('total'),
            'cash'     => (float) $sales->where('payment_method', 'cash')->sum('total'),
            'card'     => (float) $sales->where('payment_method', 'card')->sum('total'),
            'credit'   => (float) $sales->sum('credit_amount'),
        ];

        if ($by === 'item') {
            // Item lines, attributed to whoever rang the sale up.
            $lines = SaleItem::whereIn('sale_id', $sales->pluck('id'))
                ->with(['product:id,name,unit', 'sale:id,user_id'])
                ->get();

            $rows = $lines->groupBy(fn ($l) => $l->sale?->user_id . '|' . $l->product_id)
                ->map(fn ($g) => [
                    'cashier'  => $sales->firstWhere('user_id', $g->first()->sale?->user_id)?->user?->name ?? '—',
                    'label'    => $g->first()->product?->name ?? 'Deleted product',
                    'unit'     => $g->first()->product?->unit,
                    'qty'      => (float) $g->sum('quantity'),
                    'net'      => (float) $g->sum('subtotal'),
                    'cost'     => (float) $g->sum('cost'),
                    'invoices' => $g->pluck('sale_id')->unique()->count(),
                ])
                ->sortBy([['cashier', 'asc'], ['net', 'desc']])
                ->values();
        } elseif ($by === 'customer') {
            $rows = $sales->groupBy(fn ($s) => $s->user_id . '|' . ($s->customer_id ?? 0))
                ->map(fn ($g) => [
                    'cashier'  => $g->first()->user?->name ?? '—',
                    'label'    => $g->first()->customer?->name ?? 'Walk-in',
                    'unit'     => null,
                    'qty'      => null,
                    'invoices' => $g->count(),
                    'net'      => (float) $g->sum('total'),
                    'cost'     => null,
                ])
                ->sortBy([['cashier', 'asc'], ['net', 'desc']])
                ->values();
        } else {
            $rows = $sales->groupBy('user_id')
                ->map(fn ($g) => [
                    'cashier'  => $g->first()->user?->name ?? '—',
                    'label'    => null,
                    'unit'     => null,
                    'qty'      => null,
                    'invoices' => $g->count(),
                    'net'      => (float) $g->sum('total'),
                    'cash'     => (float) $g->where('payment_method', 'cash')->sum('total'),
                    'card'     => (float) $g->where('payment_method', 'card')->sum('total'),
                    'credit'   => (float) $g->sum('credit_amount'),
                    'cost'     => null,
                ])
                ->sortByDesc('net')
                ->values();
        }

        return view('reports.cashiers', [
            'range'    => $range,
            'by'       => $by,
            'rows'     => $rows,
            'totals'   => $totals,
            'filters'  => $filters,
            'cashiers' => $this->cashierOptions($branchId),
            'counters' => \App\Models\Counter::whereBranch($branchId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Buying: goods received, what was sent back, and what is still owed.
     *
     * A purchase in this system IS the goods-received note — stock and cost land
     * when the delivery is recorded, and there is no separate ordered-but-not-yet-
     * arrived document, so there is nothing to report as an outstanding order.
     */
    public function purchases(Request $request)
    {
        $range    = ReportRange::fromRequest($request);
        $branchId = CurrentBranch::id();
        $mode     = in_array($request->mode, ['summary', 'supplier', 'item', 'returns'], true) ? $request->mode : 'details';

        $supplierId = $request->supplier_id ?: null;
        $status     = $request->payment_status ?: null;

        $base = fn () => Purchase::whereBranch($branchId)
            ->whereBetween('purchase_date', [$range->fromDate(), $range->toDate()])
            ->when($supplierId, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($status, fn ($q, $v) => $q->where('payment_status', $v));

        $suppliers = \App\Models\Supplier::orderBy('name')->get(['id', 'name']);

        if ($mode === 'returns') {
            $returns = \App\Models\PurchaseReturn::with(['purchase.supplier', 'supplier', 'createdBy'])
                ->whereHas('purchase', fn ($q) => $q->whereBranch($branchId))
                ->whereBetween(DB::raw('DATE(created_at)'), [$range->fromDate(), $range->toDate()])
                ->when($supplierId, fn ($q, $v) => $q->where('supplier_id', $v))
                ->latest()->get();

            return view('reports.purchases', [
                'range' => $range, 'mode' => $mode, 'rows' => $returns, 'suppliers' => $suppliers,
                'supplierId' => $supplierId, 'status' => $status,
                'totals' => ['count' => $returns->count(), 'total' => (float) $returns->sum('return_amount'),
                             'paid' => 0.0, 'due' => 0.0],
            ]);
        }

        $purchases = $base()->with(['supplier', 'user'])->orderBy('purchase_date')->get();

        $totals = [
            'count' => $purchases->count(),
            'total' => (float) $purchases->sum('total'),
            'paid'  => (float) $purchases->sum('paid_amount'),
            'due'   => (float) $purchases->sum('balance_due'),
        ];

        $rows = match ($mode) {
            'summary' => $purchases->groupBy(fn ($p) => (string) $p->purchase_date)
                ->map(fn ($g, $d) => [
                    'label' => $d, 'count' => $g->count(),
                    'total' => (float) $g->sum('total'), 'paid' => (float) $g->sum('paid_amount'),
                    'due'   => (float) $g->sum('balance_due'),
                ])->values(),

            'supplier' => $purchases->groupBy('supplier_id')
                ->map(fn ($g) => [
                    'label' => $g->first()->supplier?->name ?? 'Unknown supplier', 'count' => $g->count(),
                    'total' => (float) $g->sum('total'), 'paid' => (float) $g->sum('paid_amount'),
                    'due'   => (float) $g->sum('balance_due'),
                ])->sortByDesc('total')->values(),

            'item' => \App\Models\PurchaseItem::whereIn('purchase_id', $purchases->pluck('id'))
                ->with('product:id,name,unit')->get()
                ->groupBy(fn ($i) => $i->product_id ?? 'custom:' . $i->name)
                ->map(fn ($g) => [
                    'label' => $g->first()->product?->name ?? $g->first()->name ?? 'Custom item',
                    'unit'  => $g->first()->product?->unit,
                    'count' => $g->pluck('purchase_id')->unique()->count(),
                    'qty'   => (float) $g->sum('quantity'),
                    'total' => (float) $g->sum('subtotal'),
                    'paid'  => 0.0, 'due' => 0.0,
                ])->sortByDesc('total')->values(),

            default => $purchases,
        };

        return view('reports.purchases', [
            'range' => $range, 'mode' => $mode, 'rows' => $rows, 'totals' => $totals,
            'suppliers' => $suppliers, 'supplierId' => $supplierId, 'status' => $status,
        ]);
    }

    /**
     * Gross profit, grouped whichever way you need to look at it.
     *
     * The menu lists item, category, supplier, location and invoice versions as
     * five reports; they are one query over the sale lines, totalled by a
     * different key. Profit is measured against the cost captured on each line
     * when it sold, and returns are netted off both revenue and cost so a
     * refunded sale stops counting as profit.
     */
    public function grossProfit(Request $request)
    {
        $range    = ReportRange::fromRequest($request);
        $branchId = CurrentBranch::id();
        $by       = in_array($request->by, ['category', 'supplier', 'location', 'invoice', 'customer'], true)
            ? $request->by : 'item';

        $lines = SaleItem::whereHas('sale', fn ($q) => $q->whereBranch($branchId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$range->fromDate(), $range->toDate()]))
            ->with(['product:id,name,unit,category_id', 'product.category:id,name',
                    'sale:id,invoice_no,created_at,branch_id,customer_id', 'sale.customer:id,name', 'sale.branch:id,name'])
            ->get();

        $returns   = $this->returnsByProduct($branchId, $range->fromDate(), $range->toDate());
        $suppliers = $by === 'supplier' ? $this->productSuppliers($lines->pluck('product_id')->unique()) : collect();

        // Returns are known per product, so they can only be netted off groupings
        // that stay product-shaped. Grouping by invoice or customer is gross.
        $netsReturns = in_array($by, ['item', 'category', 'supplier'], true);

        $keyed = $lines->groupBy(function ($l) use ($by, $suppliers) {
            return match ($by) {
                'category' => $l->product?->category?->name ?? 'Uncategorised',
                'supplier' => $suppliers[$l->product_id] ?? 'No purchase record',
                'location' => $l->sale?->branch?->name ?? '—',
                'invoice'  => $l->sale?->invoice_no ?? '—',
                'customer' => $l->sale?->customer?->name ?? 'Walk-in',
                default    => $l->product?->name ?? 'Deleted product',
            };
        });

        $rows = $keyed->map(function ($group, $label) use ($returns, $netsReturns) {
            $revenue = (float) $group->sum('subtotal');
            $cost    = (float) $group->sum('cost');
            $qty     = (float) $group->sum('quantity');

            if ($netsReturns) {
                foreach ($group->pluck('product_id')->unique() as $pid) {
                    if ($r = $returns[$pid] ?? null) {
                        $revenue -= (float) $r->revenue;
                        $cost    -= (float) $r->cogs;
                        $qty     -= (float) $r->qty;
                    }
                }
            }

            return [
                'label'   => $label,
                'qty'     => $qty,
                'unit'    => $group->first()->product?->unit,
                'lines'   => $group->count(),
                'revenue' => round($revenue, 2),
                'cost'    => round($cost, 2),
                'profit'  => round($revenue - $cost, 2),
                'margin'  => $revenue > 0 ? round(($revenue - $cost) / $revenue * 100, 1) : 0.0,
                'sale_id' => $group->first()->sale?->id,
            ];
        })->sortByDesc('profit')->values();

        return view('reports.gross_profit', [
            'range'  => $range,
            'by'     => $by,
            'rows'   => $rows,
            'netsReturns' => $netsReturns,
            'totals' => [
                'revenue' => round($rows->sum('revenue'), 2),
                'cost'    => round($rows->sum('cost'), 2),
                'profit'  => round($rows->sum('profit'), 2),
                'margin'  => $rows->sum('revenue') > 0 ? round($rows->sum('profit') / $rows->sum('revenue') * 100, 1) : 0.0,
            ],
        ]);
    }

    /**
     * Which supplier each product comes from, taken from the most recent purchase.
     *
     * Products carry no supplier of their own, so this reads it back out of the
     * buying history. The latest purchase is used rather than every supplier who
     * ever supplied it, so an item bought from two suppliers lands in one group
     * and the column totals still add up.
     */
    private function productSuppliers($productIds)
    {
        return DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->whereIn('purchase_items.product_id', $productIds)
            ->orderBy('purchases.purchase_date')
            ->orderBy('purchases.id')
            ->pluck('suppliers.name', 'purchase_items.product_id');   // later rows win
    }

    /**
     * What was handed back, by item — the money and the margin that went with it.
     */
    public function refunds(Request $request)
    {
        $range    = ReportRange::fromRequest($request);
        $branchId = CurrentBranch::id();

        $items = SaleReturnItem::with(['product:id,name,unit', 'saleReturn.sale:id,invoice_no'])
            ->whereHas('saleReturn', fn ($q) => $q
                ->whereBetween(DB::raw('DATE(created_at)'), [$range->fromDate(), $range->toDate()])
                ->whereHas('sale', fn ($s) => $s->whereBranch($branchId)))
            ->get();

        $rows = $items->groupBy('product_id')->map(fn ($g) => [
            'label'   => $g->first()->product?->name ?? 'Deleted product',
            'unit'    => $g->first()->product?->unit,
            'qty'     => (float) $g->sum('quantity'),
            'notes'   => $g->pluck('sale_return_id')->unique()->count(),
            'revenue' => (float) $g->sum('subtotal'),
            'cost'    => (float) $g->sum('cost'),
        ])->map(fn ($r) => $r + ['profit' => round($r['revenue'] - $r['cost'], 2)])
          ->sortByDesc('revenue')->values();

        return view('reports.refunds', [
            'range'  => $range,
            'rows'   => $rows,
            'totals' => [
                'qty'     => $rows->sum('qty'),
                'revenue' => round($rows->sum('revenue'), 2),
                'cost'    => round($rows->sum('cost'), 2),
                'profit'  => round($rows->sum('profit'), 2),
                'notes'   => $items->pluck('sale_return_id')->unique()->count(),
            ],
        ]);
    }

    /**
     * Net profit day by day: what was sold, what it cost, what was spent.
     */
    public function netProfit(Request $request)
    {
        $range    = ReportRange::fromRequest($request);
        $branchId = CurrentBranch::id();
        $from     = $range->fromDate();
        $to       = $range->toDate();

        $sales = Sale::whereBranch($branchId)->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('DATE(created_at) d, COUNT(*) invoices, SUM(total) revenue')
            ->groupBy('d')->pluck('revenue', 'd');
        $counts = Sale::whereBranch($branchId)->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('DATE(created_at) d, COUNT(*) c')->groupBy('d')->pluck('c', 'd');

        $cogs = SaleItem::whereHas('sale', fn ($q) => $q->whereBranch($branchId))
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween(DB::raw('DATE(sales.created_at)'), [$from, $to])
            ->selectRaw('DATE(sales.created_at) d, SUM(sale_items.cost) c')
            ->groupBy('d')->pluck('c', 'd');

        $retAmt = SaleReturn::whereHas('sale', fn ($q) => $q->whereBranch($branchId))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('DATE(created_at) d, SUM(return_amount) a')->groupBy('d')->pluck('a', 'd');

        $retCogs = SaleReturnItem::whereHas('saleReturn', fn ($q) => $q
                ->whereHas('sale', fn ($s) => $s->whereBranch($branchId)))
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->whereBetween(DB::raw('DATE(sale_returns.created_at)'), [$from, $to])
            ->selectRaw('DATE(sale_returns.created_at) d, SUM(sale_return_items.cost) c')
            ->groupBy('d')->pluck('c', 'd');

        $expenses = Expense::whereBranch($branchId)->whereBetween('expense_date', [$from, $to])
            ->selectRaw('expense_date d, SUM(amount) a')->groupBy('d')->pluck('a', 'd');

        $days = collect(array_unique(array_merge(
            $sales->keys()->all(), $retAmt->keys()->all(), $expenses->keys()->all()
        )))->sort()->values();

        $rows = $days->map(function ($d) use ($sales, $counts, $cogs, $retAmt, $retCogs, $expenses) {
            $net   = (float) ($sales[$d] ?? 0) - (float) ($retAmt[$d] ?? 0);
            $c     = (float) ($cogs[$d] ?? 0) - (float) ($retCogs[$d] ?? 0);
            $exp   = (float) ($expenses[$d] ?? 0);
            $gross = $net - $c;

            return [
                'date' => $d, 'invoices' => (int) ($counts[$d] ?? 0),
                'sales' => (float) ($sales[$d] ?? 0), 'returns' => (float) ($retAmt[$d] ?? 0),
                'net' => round($net, 2), 'cogs' => round($c, 2),
                'gross' => round($gross, 2), 'expenses' => $exp,
                'profit' => round($gross - $exp, 2),
                'margin' => $net > 0 ? round(($gross - $exp) / $net * 100, 1) : 0.0,
            ];
        });

        return view('reports.net_profit', [
            'range'  => $range,
            'rows'   => $rows,
            'totals' => [
                'invoices' => $rows->sum('invoices'), 'sales' => $rows->sum('sales'),
                'returns' => $rows->sum('returns'), 'net' => $rows->sum('net'),
                'cogs' => $rows->sum('cogs'), 'gross' => $rows->sum('gross'),
                'expenses' => $rows->sum('expenses'), 'profit' => $rows->sum('profit'),
                'margin' => $rows->sum('net') > 0 ? round($rows->sum('profit') / $rows->sum('net') * 100, 1) : 0.0,
            ],
        ]);
    }

    /**
     * The cash book: every movement through cash and bank accounts, with a
     * running balance, plus the day's hand-overs from the tills.
     *
     * Covers what the menu splits into "Cash Book", "Cash Book (Cashier Wise)",
     * "Cash Bank Details", "Bank Balance Sheet" and "Cash HandOver Details" —
     * they are all this ledger, filtered or grouped differently.
     */
    public function cashBook(Request $request)
    {
        $range    = ReportRange::fromRequest($request);
        $branchId = CurrentBranch::id();
        $view     = in_array($request->view, ['balances', 'handover'], true) ? $request->view : 'ledger';

        $accounts = Account::where('status', 'active')
            ->where(fn ($q) => $q->whereBranch($branchId)->orWhereNull('branch_id'))
            ->orderBy('type')->orderBy('name')
            ->get();

        $accountId = $request->account_id ?: null;
        $type      = in_array($request->type, ['cash', 'bank'], true) ? $request->type : null;

        $scope = $accounts->when($type, fn ($c) => $c->where('type', $type))
            ->when($accountId, fn ($c) => $c->where('id', (int) $accountId));

        if ($view === 'handover') {
            // What each till actually sent in at close — the cash side of the book,
            // traced back to the shift it came from.
            $sessions = \App\Models\CounterSession::with(['counter', 'closedBy', 'depositAccount'])
                ->whereBranch($branchId)
                ->where('status', 'closed')
                ->whereBetween(DB::raw('DATE(closed_at)'), [$range->fromDate(), $range->toDate()])
                ->orderByDesc('closed_at')
                ->get();

            return view('reports.cash_book', [
                'range' => $range, 'view' => $view, 'accounts' => $accounts,
                'accountId' => $accountId, 'type' => $type,
                'sessions' => $sessions,
                'totals' => [
                    'counted'  => (float) $sessions->sum('closing_balance'),
                    'sent'     => (float) $sessions->sum('deposit_amount'),
                    'kept'     => (float) $sessions->sum('float_retained'),
                    'variance' => (float) $sessions->sum('variance'),
                ],
                'entries' => collect(), 'opening' => 0.0, 'balances' => collect(),
            ]);
        }

        if ($view === 'balances') {
            // Where every account stood at the end of the period, and what moved.
            $balances = $scope->map(function ($a) use ($range) {
                $before = (float) AccountTransaction::where('account_id', $a->id)
                    ->whereDate('occurred_at', '<', $range->fromDate())
                    ->selectRaw("COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END),0) t")->value('t');

                $period = AccountTransaction::where('account_id', $a->id)
                    ->whereBetween(DB::raw('DATE(occurred_at)'), [$range->fromDate(), $range->toDate()])
                    ->selectRaw("
                        COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE 0 END),0) money_in,
                        COALESCE(SUM(CASE WHEN direction='debit'  THEN amount ELSE 0 END),0) money_out
                    ")->first();

                return [
                    'account'   => $a,
                    'opening'   => $before,
                    'money_in'  => (float) $period->money_in,
                    'money_out' => (float) $period->money_out,
                    'closing'   => $before + (float) $period->money_in - (float) $period->money_out,
                ];
            })->values();

            return view('reports.cash_book', [
                'range' => $range, 'view' => $view, 'accounts' => $accounts,
                'accountId' => $accountId, 'type' => $type,
                'balances' => $balances,
                'totals' => [
                    'opening'   => $balances->sum('opening'),
                    'money_in'  => $balances->sum('money_in'),
                    'money_out' => $balances->sum('money_out'),
                    'closing'   => $balances->sum('closing'),
                ],
                'entries' => collect(), 'opening' => 0.0, 'sessions' => collect(),
            ]);
        }

        // Ledger: the movements themselves, with a running balance carried in
        // from whatever the accounts held before the period opened.
        $ids = $scope->pluck('id');

        $opening = (float) AccountTransaction::whereIn('account_id', $ids)
            ->whereDate('occurred_at', '<', $range->fromDate())
            ->selectRaw("COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END),0) t")->value('t');

        $entries = AccountTransaction::with(['account', 'counterparty', 'createdBy'])
            ->whereIn('account_id', $ids)
            ->whereBetween(DB::raw('DATE(occurred_at)'), [$range->fromDate(), $range->toDate()])
            ->orderBy('occurred_at')->orderBy('id')
            ->get();

        return view('reports.cash_book', [
            'range' => $range, 'view' => $view, 'accounts' => $accounts,
            'accountId' => $accountId, 'type' => $type,
            'entries' => $entries, 'opening' => $opening,
            'totals' => [
                'opening'   => $opening,
                'money_in'  => (float) $entries->where('direction', 'credit')->sum('amount'),
                'money_out' => (float) $entries->where('direction', 'debit')->sum('amount'),
                'closing'   => $opening
                    + (float) $entries->where('direction', 'credit')->sum('amount')
                    - (float) $entries->where('direction', 'debit')->sum('amount'),
            ],
            'balances' => collect(), 'sessions' => collect(),
        ]);
    }

    /** The filters every invoice-based report shares. */
    private function invoiceScope($query, ?int $branchId, ReportRange $range, array $filters)
    {
        return $query->whereBranch($branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$range->fromDate(), $range->toDate()])
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['counter_id'] ?? null, fn ($q, $v) => $q->where('counter_id', $v))
            ->when($filters['payment_method'] ?? null, fn ($q, $v) => $q->where('payment_method', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v));
    }

    /** Users who actually rang up a sale in this branch — not every account. */
    private function cashierOptions(?int $branchId)
    {
        return \App\Models\User::whereIn(
                'id',
                Sale::whereBranch($branchId)->select('user_id')->distinct()
            )->orderBy('name')->get(['id', 'name']);
    }

    // ── HRM reports ───────────────────────────────────────────────────────

    public function hrmAttendance(Request $request)
    {
        $range   = ReportRange::fromRequest($request);
        $summary = $this->attendanceSummary(CurrentBranch::id(), $range->fromDate(), $range->toDate());

        $totals = [
            'present' => $summary->sum('present'),
            'leave'   => $summary->sum('leave'),
            'absent'  => $summary->sum('absent'),
            'hours'   => round($summary->sum('hours'), 1),
            'ot'      => round($summary->sum('ot'), 1),
        ];

        return view('reports.hrm_attendance', compact('range', 'summary', 'totals'));
    }

    public function hrmPayroll(Request $request)
    {
        $range    = ReportRange::fromRequest($request);
        $payrolls = $this->payrollRegister(CurrentBranch::id(), $range->fromDate(), $range->toDate());

        $totals = [
            'gross'    => $payrolls->sum('gross_salary'),
            'epf_emp'  => $payrolls->sum('epf_employee'),
            'deduct'   => $payrolls->sum('deductions'),
            'net'      => $payrolls->sum('net_salary'),
            'employer' => $payrolls->sum(fn ($p) => $p->employerCost()),
        ];

        return view('reports.hrm_payroll', compact('range', 'payrolls', 'totals'));
    }

    public function hrmLeave(Request $request)
    {
        $range   = ReportRange::fromRequest($request);
        $year    = (int) Carbon::parse($range->fromDate())->year;
        $summary = $this->leaveSummary(CurrentBranch::id(), $year);

        return view('reports.hrm_leave', compact('range', 'summary', 'year'));
    }

    public function export(Request $request, string $type)
    {
        [$from, $to] = $this->dateRange($request);
        $format      = strtolower($request->input('format', 'pdf'));
        $branchId    = CurrentBranch::id();

        // This one route serves every export type, so `reports.export` alone is not
        // enough for the sensitive ones — otherwise anyone who can export a rate
        // list could also pull the payroll register.
        $extraPermission = [
            'hrm_attendance' => 'hrm.view',
            'hrm_payroll'    => 'hrm.payroll.manage',
            'hrm_leave'      => 'hrm.view',
            'profit_loss'    => 'reports.profit',
            'product_sales'  => 'reports.profit',
            'net_profit'     => 'reports.profit',
        ][$type] ?? match (true) {
            str_starts_with($type, 'gross_profit_') => 'reports.profit',
            str_starts_with($type, 'purchases_')    => 'purchases.view',
            default                                 => null,
        };

        abort_if($extraPermission && ! auth()->user()->can($extraPermission), 403);

        $spec = $this->exportSpec($type, $branchId, $from, $to);
        abort_unless($spec, 404);

        [$title, $headers, $rows] = $spec;
        $basename = $type . '-' . $from . '-to-' . $to;

        if ($format === 'pdf') {
            return Pdf::loadView('reports.export.table_pdf', [
                    'title'   => $title,
                    'period'  => $from . ' → ' . $to,
                    'branch'  => CurrentBranch::name(),
                    'headers' => $headers,
                    'rows'    => $rows,
                ])->setPaper('A4')
                  ->download($basename . '.pdf');
        }

        // The UI sends 'excel'; accept 'xlsx' too. Anything else falls back to CSV.
        return $this->downloadSpreadsheet($headers, $rows, in_array($format, ['excel', 'xlsx']) ? 'xlsx' : 'csv', $basename);
    }

    /**
     * [$title, $headers, $rows] for each export the report pages link to,
     * or null for an unknown type. Money is pre-formatted so PDF and
     * spreadsheet output stay identical.
     */
    private function exportSpec(string $type, ?int $branchId, string $from, string $to): ?array
    {
        $money = fn ($v) => number_format((float) $v, 2);

        switch ($type) {
            case 'sales':
                $sales = Sale::whereBranch($branchId)
                    ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                    ->with('customer')->latest()->get();
                $rows = $sales->map(fn ($s) => [
                    $s->invoice_no, $s->created_at->format('Y-m-d H:i'),
                    $s->customer?->name ?? 'Walk-in', ucfirst($s->payment_method),
                    ucfirst($s->status), $money($s->total),
                ])->all();
                $rows[] = ['', '', '', '', 'Total', $money($sales->sum('total'))];
                return ['Sales report', ['Invoice', 'Date', 'Customer', 'Method', 'Status', 'Total (Rs.)'], $rows];

            case 'expenses':
                $expenses = Expense::whereBranch($branchId)
                    ->whereBetween('expense_date', [$from, $to])
                    ->with('category')->orderBy('expense_date')->get();
                $rows = $expenses->map(fn ($e) => [
                    $e->expense_date, $e->category?->name ?? '—', $e->description, $money($e->amount),
                ])->all();
                $rows[] = ['', '', 'Total', $money($expenses->sum('amount'))];
                return ['Expenses report', ['Date', 'Category', 'Description', 'Amount (Rs.)'], $rows];

            case 'payments':
                // payments has no branch column — scope through the account it hit.
                $payments = Payment::with('account')
                    ->when($branchId, fn ($q, $b) => $q->whereHas('account', fn ($a) => $a->where('branch_id', $b)))
                    ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                    ->latest()->get();
                $rows = $payments->map(fn ($p) => [
                    $p->reference_no, $p->created_at->format('Y-m-d H:i'),
                    $p->type === 'payment_in' ? 'In' : 'Out',
                    ucfirst($p->method), $p->account?->name ?? '—', $money($p->amount),
                ])->all();
                $in  = $payments->where('type', 'payment_in')->sum('amount');
                $out = $payments->where('type', '!=', 'payment_in')->sum('amount');
                $rows[] = ['', '', '', '', 'Total in', $money($in)];
                $rows[] = ['', '', '', '', 'Total out', $money($out)];
                return ['Payments report', ['Reference', 'Date', 'Type', 'Method', 'Account', 'Amount (Rs.)'], $rows];

            case 'product_sales':
                $lines = SaleItem::whereHas('sale', fn ($q) => $q->whereBranch($branchId)
                        ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
                    ->selectRaw('product_id, SUM(quantity) qty, SUM(subtotal) revenue, SUM(COALESCE(cost,0)) cost')
                    ->groupBy('product_id')->with('product')
                    ->orderByRaw('SUM(subtotal) DESC')->get();
                $rows = $lines->map(fn ($l) => [
                    $l->product?->name ?? 'Deleted product', rtrim(rtrim(number_format((float) $l->qty, 3), '0'), '.'),
                    $money($l->revenue), $money($l->cost), $money($l->revenue - $l->cost),
                ])->all();
                return ['Product sales report', ['Product', 'Qty sold', 'Revenue (Rs.)', 'Cost (Rs.)', 'Profit (Rs.)'], $rows];

            case 'profit_loss':
                $returns  = $this->returnTotals($branchId, $from, $to);
                $revenue  = Sale::whereBranch($branchId)->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->sum('total');
                $cogs     = SaleItem::whereHas('sale', fn ($q) => $q->whereBranch($branchId)
                                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))->sum('cost');
                $expenses = Expense::whereBranch($branchId)->whereBetween('expense_date', [$from, $to])->sum('amount');
                $net      = $revenue - $returns['amount'];
                $gross    = $net - $cogs + $returns['cogs'];
                $rows = [
                    ['Gross sales',        $money($revenue)],
                    ['Sales returns',      '-' . $money($returns['amount'])],
                    ['Net sales',          $money($net)],
                    ['Cost of goods sold', '-' . $money($cogs - $returns['cogs'])],
                    ['Gross profit',       $money($gross)],
                    ['Expenses',           '-' . $money($expenses)],
                    ['Net profit',         $money($gross - $expenses)],
                ];
                return ['Profit & loss', ['', 'Amount (Rs.)'], $rows];

            case 'rate_list':
                $rows = Product::where('status', 'active')->orderBy('name')->get()
                    ->map(fn ($p) => [
                        $p->name, $p->barcode ?? '—', $p->unit,
                        $p->mrp ? $money($p->mrp) : '—', $money($p->sale_price),
                    ])->all();
                return ['Rate list', ['Product', 'Barcode', 'Unit', 'MRP (Rs.)', 'Sale price (Rs.)'], $rows];

            case 'stock_alert':
                $rows = Product::where('status', 'active')->where('min_stock', '>', 0)
                    ->with('stocks')->orderBy('name')->get()
                    ->filter(fn ($p) => $p->stockForBranch($branchId) < $p->min_stock)
                    ->map(fn ($p) => [
                        $p->name, $p->barcode ?? '—',
                        rtrim(rtrim(number_format($p->stockForBranch($branchId), 3), '0'), '.'),
                        $p->min_stock,
                    ])->values()->all();
                return ['Stock alert (below minimum)', ['Product', 'Barcode', 'On hand', 'Minimum'], $rows];

            case 'stock':
                $rows = Stock::whereBranch($branchId)->with('product')->get()
                    ->map(fn ($s) => [
                        $s->product?->name ?? 'Deleted product',
                        rtrim(rtrim(number_format((float) $s->quantity, 3), '0'), '.'),
                    ])->all();
                return ['Stock on hand', ['Product', 'Quantity'], $rows];

            case 'invoices_details':
            case 'invoices_summary':
            case 'invoices_cancelled':
                return $this->invoiceExport(substr($type, 9), $branchId, $from, $to, $money);

            case 'gross_profit_item':
            case 'gross_profit_category':
            case 'gross_profit_supplier':
            case 'gross_profit_location':
            case 'gross_profit_invoice':
            case 'gross_profit_customer':
                $by   = substr($type, 13);
                $d    = $this->grossProfit(request()->merge(['by' => $by]))->getData();
                $head = ['item'=>'Item','category'=>'Category','supplier'=>'Supplier','location'=>'Branch','invoice'=>'Invoice','customer'=>'Customer'][$by];
                $qty  = in_array($by, ['item', 'category', 'supplier'], true);
                $rows = $d['rows']->map(fn ($r) => [
                    $r['label'], $qty ? $this->trim($r['qty'], 3) : $r['lines'],
                    $money($r['revenue']), $money($r['cost']), $money($r['profit']), $r['margin'] . '%',
                ])->all();
                $rows[] = ['Total', '', $money($d['totals']['revenue']), $money($d['totals']['cost']),
                           $money($d['totals']['profit']), $d['totals']['margin'] . '%'];

                return ["Gross profit by " . strtolower($head),
                    [$head, $qty ? 'Qty' : 'Lines', 'Revenue (Rs.)', 'Cost (Rs.)', 'Profit (Rs.)', 'Margin'], $rows];

            case 'refunds':
                $d    = $this->refunds(request())->getData();
                $rows = $d['rows']->map(fn ($r) => [
                    $r['label'], $this->trim($r['qty'], 3) . ' ' . $r['unit'], $r['notes'],
                    $money($r['revenue']), $money($r['cost']), $money($r['profit']),
                ])->all();
                $rows[] = ['Total', $this->trim($d['totals']['qty'], 3), $d['totals']['notes'],
                           $money($d['totals']['revenue']), $money($d['totals']['cost']), $money($d['totals']['profit'])];

                return ['Refunds & cancellations',
                    ['Item', 'Qty back', 'Credit notes', 'Refunded (Rs.)', 'Cost back (Rs.)', 'Margin lost (Rs.)'], $rows];

            case 'net_profit':
                $d    = $this->netProfit(request())->getData();
                $t    = $d['totals'];
                $rows = $d['rows']->map(fn ($r) => [
                    $r['date'], $r['invoices'], $money($r['sales']), $money($r['returns']), $money($r['net']),
                    $money($r['cogs']), $money($r['gross']), $money($r['expenses']), $money($r['profit']), $r['margin'] . '%',
                ])->all();
                $rows[] = ['Total', $t['invoices'], $money($t['sales']), $money($t['returns']), $money($t['net']),
                           $money($t['cogs']), $money($t['gross']), $money($t['expenses']), $money($t['profit']), $t['margin'] . '%'];

                return ['Net profit by date',
                    ['Date', 'Invoices', 'Sales', 'Returns', 'Net sales', 'Cost of goods', 'Gross profit', 'Expenses', 'Net profit', 'Margin'], $rows];

            case 'purchases_details':
            case 'purchases_summary':
            case 'purchases_supplier':
            case 'purchases_item':
            case 'purchases_returns':
                return $this->purchaseExport(substr($type, 10), $money);

            case 'cash_book_ledger':
            case 'cash_book_balances':
            case 'cash_book_handover':
                return $this->cashBookExport(substr($type, 10), $money);

            case 'cashiers_cashier':
            case 'cashiers_customer':
            case 'cashiers_item':
                return $this->cashierExport(substr($type, 9), $money);

            case 'hrm_attendance':
                $summary = $this->attendanceSummary($branchId, $from, $to);
                $rows = $summary->map(fn ($r) => [
                    $r['name'], $r['role'],
                    $r['present'], $r['half_day'], $r['leave'], $r['absent'],
                    $this->trim($r['hours']), $this->trim($r['ot']),
                ])->all();
                $rows[] = [
                    'Total', '',
                    $summary->sum('present'), $summary->sum('half_day'),
                    $summary->sum('leave'), $summary->sum('absent'),
                    $this->trim($summary->sum('hours')), $this->trim($summary->sum('ot')),
                ];
                return ['Attendance summary',
                    ['Staff', 'Job title', 'Present', 'Half day', 'Leave', 'Absent', 'Hours', 'Overtime'], $rows];

            case 'hrm_payroll':
                $payrolls = $this->payrollRegister($branchId, $from, $to);
                $rows = $payrolls->map(fn ($p) => [
                    $p->staff?->name ?? '—', $p->periodLabel(),
                    $money($p->contract_salary), $money($p->basic_salary), $money($p->overtime_pay),
                    $money($p->allowances), $money($p->gross_salary),
                    $money($p->epf_employee), $money($p->deductions), $money($p->net_salary),
                    $money($p->epf_employer), $money($p->etf), $money($p->employerCost()),
                    ucfirst($p->status),
                ])->all();
                $rows[] = [
                    'Total', '',
                    $money($payrolls->sum('contract_salary')), $money($payrolls->sum('basic_salary')),
                    $money($payrolls->sum('overtime_pay')), $money($payrolls->sum('allowances')),
                    $money($payrolls->sum('gross_salary')), $money($payrolls->sum('epf_employee')),
                    $money($payrolls->sum('deductions')), $money($payrolls->sum('net_salary')),
                    $money($payrolls->sum('epf_employer')), $money($payrolls->sum('etf')),
                    $money($payrolls->sum(fn ($p) => $p->employerCost())), '',
                ];
                return ['Payroll register',
                    ['Staff', 'Period', 'Contract', 'Basic earned', 'Overtime', 'Allowances', 'Gross',
                     'EPF 8%', 'Deductions', 'Net pay', 'EPF 12%', 'ETF 3%', 'Employer cost', 'Status'], $rows];

            case 'hrm_leave':
                $year = (int) Carbon::parse($from)->year;
                $rows = [];
                foreach ($this->leaveSummary($branchId, $year) as $entry) {
                    foreach ($entry['balances'] as $b) {
                        $rows[] = [
                            $entry['staff']->name, $entry['staff']->role, $b['label'],
                            $b['tracked'] ? $this->trim($b['entitled']) : '—',
                            $this->trim($b['used']),
                            $b['tracked'] ? $this->trim($b['remaining']) : '—',
                        ];
                    }
                }
                return ["Leave summary {$year}",
                    ['Staff', 'Job title', 'Leave type', 'Entitled', 'Used', 'Remaining'], $rows];
        }

        return null;
    }

    /**
     * The invoice report in export form. Reads the same filters off the request
     * as the screen does, so what downloads matches what was on screen rather
     * than silently exporting everything.
     */
    private function invoiceExport(string $mode, ?int $branchId, string $from, string $to, callable $money): array
    {
        $request = request();
        $range   = ReportRange::fromRequest($request);
        $filters = [
            'user_id'        => $request->user_id ?: null,
            'counter_id'     => $request->counter_id ?: null,
            'payment_method' => $request->payment_method ?: null,
            'status'         => $request->status ?: null,
        ];

        if ($mode === 'cancelled') {
            $returns = SaleReturn::with(['sale.customer', 'createdBy'])
                ->whereHas('sale', fn ($q) => $this->invoiceScope($q, $branchId, $range, $filters))
                ->whereBetween(DB::raw('DATE(created_at)'), [$range->fromDate(), $range->toDate()])
                ->latest()->get();

            $rows = $returns->map(fn ($r) => [
                $r->created_at->format('Y-m-d H:i'), $r->credit_note_no,
                $r->sale?->invoice_no ?? '—', $r->sale?->customer?->name ?? 'Walk-in',
                $r->reason, $money($r->return_amount), $r->createdBy?->name ?? '—',
            ])->all();
            $rows[] = ['', '', '', '', 'Total refunded', $money($returns->sum('return_amount')), ''];

            return ['Credit notes', ['Date', 'Credit note', 'Invoice', 'Customer', 'Reason', 'Refund (Rs.)', 'By'], $rows];
        }

        $sales = $this->invoiceScope(Sale::query(), $branchId, $range, $filters)
            ->with(['customer', 'user', 'counter'])->orderBy('created_at')->get();

        $disc = fn ($s) => (float) $s->discount_amount + (float) $s->coupon_discount;

        if ($mode === 'summary') {
            $rows = $sales->groupBy(fn ($s) => $s->created_at->toDateString())
                ->map(fn ($day, $date) => [
                    $date, $day->count(), $money($day->sum('subtotal')),
                    $money($day->sum($disc)), $money($day->sum('tax_amount')),
                    $money($day->sum('total')), $money($day->sum('paid_amount')),
                ])->values()->all();
            $rows[] = ['Total', $sales->count(), $money($sales->sum('subtotal')),
                $money($sales->sum($disc)), $money($sales->sum('tax_amount')),
                $money($sales->sum('total')), $money($sales->sum('paid_amount'))];

            return ['Invoice summary', ['Date', 'Invoices', 'Gross', 'Discount', 'Tax', 'Net sales', 'Collected'], $rows];
        }

        $rows = $sales->map(fn ($s) => [
            $s->created_at->format('Y-m-d H:i'), $s->invoice_no,
            $s->customer?->name ?? 'Walk-in', $s->user?->name ?? '—', $s->counter?->name ?? '—',
            ucfirst(str_replace('_', ' ', $s->payment_method)),
            $money($s->subtotal), $money($disc($s)), $money($s->tax_amount),
            $money($s->total), $money($s->paid_amount),
            $money(max(0, (float) $s->total - (float) $s->paid_amount)), ucfirst($s->status),
        ])->all();
        $rows[] = ['Total', '', '', '', '', '', $money($sales->sum('subtotal')), $money($sales->sum($disc)),
            $money($sales->sum('tax_amount')), $money($sales->sum('total')), $money($sales->sum('paid_amount')),
            $money($sales->sum(fn ($s) => max(0, (float) $s->total - (float) $s->paid_amount))), ''];

        return ['Invoice details',
            ['Date', 'Invoice', 'Customer', 'Cashier', 'Counter', 'Payment', 'Gross', 'Discount', 'Tax', 'Net', 'Paid', 'Due', 'Status'],
            $rows];
    }

    /** Cash book in export form, reusing the screen's own query. */
    /**
     * Purchase exports reuse the screen's own query, so whatever supplier or
     * payment filter is showing is what gets exported.
     */
    private function purchaseExport(string $mode, callable $money): array
    {
        $data  = $this->purchases(request()->merge(['mode' => $mode]))->getData();
        $rows  = $data['rows'];
        $t     = $data['totals'];
        $trim  = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');

        if ($mode === 'details') {
            $out = $rows->map(fn ($p) => [
                $p->purchase_date, $p->bill_no, $p->supplier?->name ?? 'Unknown supplier',
                $p->user?->name ?? '—', $p->due_date ?: '—',
                $money($p->total), $money($p->paid_amount), $money($p->balance_due), ucfirst($p->payment_status),
            ])->all();
            $out[] = ['Total (' . $t['count'] . ')', '', '', '', '',
                      $money($t['total']), $money($t['paid']), $money($t['due']), ''];

            return ['Goods received (GRN)',
                ['Date', 'Bill no', 'Supplier', 'Received by', 'Due', 'Total (Rs.)', 'Paid (Rs.)', 'Balance (Rs.)', 'Status'], $out];
        }

        if ($mode === 'returns') {
            $out = $rows->map(fn ($r) => [
                $r->created_at->format('Y-m-d'), $r->dr_note_no, $r->purchase?->bill_no ?? '—',
                $r->supplier?->name ?? $r->purchase?->supplier?->name ?? '—',
                $r->reason ?: '—', $r->credit_method ? ucfirst(str_replace('_', ' ', $r->credit_method)) : '—',
                $r->createdBy?->name ?? '—', $money($r->return_amount),
            ])->all();
            $out[] = ['Total (' . $t['count'] . ')', '', '', '', '', '', '', $money($t['total'])];

            return ['Purchase returns (Dr. notes)',
                ['Date', 'Dr. note no', 'Against bill', 'Supplier', 'Reason', 'Credited as', 'Raised by', 'Amount (Rs.)'], $out];
        }

        $heading = ['summary' => 'Date', 'supplier' => 'Supplier', 'item' => 'Item'][$mode];

        if ($mode === 'item') {
            $out = $rows->map(fn ($r) => [
                $r['label'], $r['count'], $trim($r['qty']) . ' ' . ($r['unit'] ?? ''), $money($r['total']),
            ])->all();
            $out[] = ['Total (' . $rows->count() . ')', $t['count'], '', $money($rows->sum('total'))];

            return ['Purchases by item', [$heading, 'Deliveries', 'Qty received', 'Value (Rs.)'], $out];
        }

        $out = $rows->map(fn ($r) => [
            $r['label'], $r['count'], $money($r['total']), $money($r['paid']), $money($r['due']),
        ])->all();
        $out[] = ['Total (' . $rows->count() . ')', $t['count'], $money($t['total']), $money($t['paid']), $money($t['due'])];

        return ['Purchases ' . ($mode === 'summary' ? 'by date' : 'by supplier'),
            [$heading, 'Deliveries', 'Total (Rs.)', 'Paid (Rs.)', 'Still owed (Rs.)'], $out];
    }

    private function cashBookExport(string $view, callable $money): array
    {
        $data = $this->cashBook(request()->merge(['view' => $view]))->getData();
        $t    = $data['totals'];

        if ($view === 'handover') {
            $rows = $data['sessions']->map(fn ($s) => [
                $s->closed_at?->format('Y-m-d H:i'), $s->counter?->name ?? '—', $s->closedBy?->name ?? '—',
                $money($s->expected_closing), $money($s->closing_balance), $money($s->variance),
                $money($s->deposit_amount),
                $s->deposit_denoms ? collect($s->deposit_denoms)->map(fn ($q, $d) => $q . ' x ' . number_format((int) $d))->implode(' · ') : '',
                $s->depositAccount?->name ?? '—', $money($s->float_retained),
            ])->all();
            $rows[] = ['Total', '', '', '', $money($t['counted']), $money($t['variance']), $money($t['sent']), '', '', $money($t['kept'])];

            return ['Till hand-overs',
                ['Closed', 'Counter', 'Closed by', 'Expected', 'Counted', 'Variance', 'Sent', 'Notes sent', 'Into', 'Left as float'], $rows];
        }

        if ($view === 'balances') {
            $rows = $data['balances']->map(fn ($b) => [
                $b['account']->name, ucfirst($b['account']->type),
                $money($b['opening']), $money($b['money_in']), $money($b['money_out']), $money($b['closing']),
            ])->all();
            $rows[] = ['Total', '', $money($t['opening']), $money($t['money_in']), $money($t['money_out']), $money($t['closing'])];

            return ['Account balances', ['Account', 'Type', 'Opening', 'Money in', 'Money out', 'Closing'], $rows];
        }

        $running = (float) $data['opening'];
        $rows    = [['', '', 'Brought forward', '', '', '', $money($running)]];

        foreach ($data['entries'] as $e) {
            $running += $e->direction === 'credit' ? (float) $e->amount : -(float) $e->amount;
            $rows[] = [
                $e->occurred_at?->format('Y-m-d H:i'), $e->account?->name ?? '—',
                $e->label() . ($e->counterparty ? ' · ' . $e->counterparty->name : ''),
                $e->reference ?: '',
                $e->direction === 'credit' ? $money($e->amount) : '',
                $e->direction === 'credit' ? '' : $money($e->amount),
                $money($running),
            ];
        }
        $rows[] = ['', '', 'Carried forward', '', $money($t['money_in']), $money($t['money_out']), $money($t['closing'])];

        return ['Cash book', ['Date', 'Account', 'Description', 'Reference', 'In', 'Out', 'Balance'], $rows];
    }

    /**
     * Cashier report in export form. Re-runs the screen's own grouping through
     * the controller action rather than repeating it, so the two can't drift.
     */
    private function cashierExport(string $by, callable $money): array
    {
        $data = $this->cashiers(request()->merge(['by' => $by]))->getData();
        $rows = $data['rows'];
        $t    = $data['totals'];

        if ($by === 'item') {
            $out = $rows->map(fn ($r) => [
                $r['cashier'], $r['label'], $this->trim($r['qty'], 3) . ' ' . $r['unit'],
                $r['invoices'], $money($r['net']), $money($r['cost']), $money($r['net'] - $r['cost']),
            ])->all();

            return ['Cashier item sales',
                ['Cashier', 'Item', 'Qty', 'Invoices', 'Net sales (Rs.)', 'Cost (Rs.)', 'Profit (Rs.)'], $out];
        }

        if ($by === 'customer') {
            $out = $rows->map(fn ($r) => [$r['cashier'], $r['label'], $r['invoices'], $money($r['net'])])->all();
            $out[] = ['Total', '', $t['invoices'], $money($t['net'])];

            return ['Cashier customer summary', ['Cashier', 'Customer', 'Invoices', 'Net sales (Rs.)'], $out];
        }

        $out = $rows->map(fn ($r) => [
            $r['cashier'], $r['invoices'], $money($r['net']),
            $money($r['cash']), $money($r['card']), $money($r['credit']),
        ])->all();
        $out[] = ['Total', $t['invoices'], $money($t['net']), $money($t['cash']), $money($t['card']), $money($t['credit'])];

        return ['Cashier sales summary',
            ['Cashier', 'Invoices', 'Net sales (Rs.)', 'Cash (Rs.)', 'Card (Rs.)', 'On credit (Rs.)'], $out];
    }

    /** Trailing-zero-free number, for day/hour counts that are usually whole. */
    private function trim($value, int $dp = 1): string
    {
        return rtrim(rtrim(number_format((float) $value, $dp), '0'), '.');
    }

    /**
     * Per-staff attendance totals for a period. Branch-scoped through the staff
     * record, since attendance has no branch column of its own.
     */
    private function attendanceSummary(?int $branchId, string $from, string $to)
    {
        $staff = \App\Models\Staff::whereBranch($branchId)->orderBy('name')->get();

        $rows = \App\Models\Attendance::whereIn('staff_id', $staff->pluck('id'))
            ->whereBetween('date', [$from, $to])
            ->get()
            ->groupBy('staff_id');

        return $staff->map(function ($s) use ($rows) {
            $own = $rows->get($s->id, collect());

            return [
                'staff'    => $s,
                'name'     => $s->name,
                'role'     => $s->role,
                'present'  => $own->where('status', 'present')->count(),
                'half_day' => $own->where('status', 'half_day')->count(),
                'leave'    => $own->where('status', 'leave')->count(),
                'absent'   => $own->where('status', 'absent')->count(),
                'hours'    => round((float) $own->sum('worked_hours'), 1),
                'ot'       => round((float) $own->sum('overtime_hours'), 1),
            ];
        });
    }

    /**
     * Payroll rows whose PERIOD overlaps the selected range. Payroll is stored as
     * month/year rather than a date, so the range is matched against the period
     * itself — picking "this month" finds this month's payroll even though the
     * row was generated later.
     */
    private function payrollRegister(?int $branchId, string $from, string $to)
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate   = Carbon::parse($to)->endOfDay();

        return \App\Models\Payroll::with('staff')
            ->whereHas('staff', fn ($q) => $q->whereBranch($branchId))
            ->get()
            ->filter(function ($p) use ($fromDate, $toDate) {
                $start = Carbon::create($p->year, $p->month, 1)->startOfMonth();

                return $start->lessThanOrEqualTo($toDate) && $start->copy()->endOfMonth()->greaterThanOrEqualTo($fromDate);
            })
            ->sortBy([fn ($a, $b) => [$b->year, $b->month] <=> [$a->year, $a->month], fn ($a, $b) => strcmp($a->staff?->name, $b->staff?->name)])
            ->values();
    }

    /** Entitled / used / remaining per staff member for a year. */
    private function leaveSummary(?int $branchId, int $year)
    {
        return \App\Models\Staff::whereBranch($branchId)->orderBy('name')->get()
            ->map(fn ($s) => [
                'staff'    => $s,
                'balances' => \App\Support\LeaveBalance::for($s, $year),
            ]);
    }

    /** Excel/CSV download via OpenSpout (same pattern as the products export). */
    private function downloadSpreadsheet(array $headers, array $rows, string $format, string $basename)
    {
        $writer = $format === 'xlsx'
            ? new \OpenSpout\Writer\XLSX\Writer()
            : new \OpenSpout\Writer\CSV\Writer();

        $tmp = tempnam(sys_get_temp_dir(), 'rpt');
        $writer->openToFile($tmp);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($headers));
        foreach ($rows as $row) {
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_map(fn ($v) => $v ?? '', $row)));
        }
        $writer->close();

        $mime = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv';

        return response()->download($tmp, $basename . '.' . $format, ['Content-Type' => $mime])
                         ->deleteFileAfterSend(true);
    }
}
