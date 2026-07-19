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
            ['title' => 'Sales',            'desc' => 'Invoices, baskets, peak hours',       'icon' => 'ti-shopping-cart',   'color' => '#4ade80', 'url' => route('reports.sales_summary', $q)],
            ['title' => 'Revenue & Profit', 'desc' => 'Revenue, COGS, margin, net profit',   'icon' => 'ti-trending-up',     'color' => '#a5b4fc', 'url' => route('reports.profit_loss', $q)],
            ['title' => 'Product sales',    'desc' => 'Best sellers and profit per product', 'icon' => 'ti-package',         'color' => '#60a5fa', 'url' => route('reports.product_sales', $q)],
            ['title' => 'Purchases',        'desc' => 'Buying, payables, top suppliers',     'icon' => 'ti-truck-delivery',  'color' => '#fbbf24', 'url' => null],
            ['title' => 'Stock movement',   'desc' => 'In / out, write-offs, stock value',   'icon' => 'ti-transfer',        'color' => '#2dd4bf', 'url' => null],
            ['title' => 'Stock summary',    'desc' => 'On-hand value by product',            'icon' => 'ti-boxes',           'color' => '#60a5fa', 'url' => route('reports.stock_summary', $q)],
            ['title' => 'Low stock alerts', 'desc' => 'Items at or below reorder level',     'icon' => 'ti-alert-triangle',  'color' => '#f87171', 'url' => route('reports.stock_alert', $q)],
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

        $expenses = Expense::with(['category', 'account'])
            ->whereBranch($branchId)
            ->whereBetween('expense_date', [$from, $to])
            ->latest('expense_date')
            ->paginate(20);

        $byCategory = Expense::whereBranch($branchId)
            ->whereBetween('expense_date', [$from, $to])
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->with('category')
            ->groupBy('expense_category_id')
            ->get();

        $total = $expenses->sum('amount');

        return view('reports.expenses', compact('expenses', 'byCategory', 'total', 'from', 'to'));
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

    // Export any report as PDF / Excel / CSV
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
        ][$type] ?? null;

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
