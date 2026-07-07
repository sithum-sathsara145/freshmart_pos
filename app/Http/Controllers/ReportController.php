<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
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
    private function returnTotals(int $branchId, string $from, string $to): array
    {
        $amount = SaleReturn::whereHas('sale', fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum('return_amount');

        $cogs = SaleReturnItem::whereHas('saleReturn', fn($q) => $q
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->whereHas('sale', fn($s) => $s->where('branch_id', $branchId)))
            ->sum('cost');

        return ['amount' => (float) $amount, 'cogs' => (float) $cogs];
    }

    /** Per-product returned qty / revenue / cogs in the period (product_id keyed). */
    private function returnsByProduct(int $branchId, string $from, string $to)
    {
        return SaleReturnItem::selectRaw('product_id, SUM(quantity) as qty, SUM(subtotal) as revenue, SUM(cost) as cogs')
            ->whereHas('saleReturn', fn($q) => $q
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->whereHas('sale', fn($s) => $s->where('branch_id', $branchId)))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');
    }

    // Profit & Loss
    public function profitLoss(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId    = auth()->user()->branch_id;

        // Sales are immutable; count gross then net out returns recorded in the period.
        $returns = $this->returnTotals($branchId, $from, $to);

        $salesRevenue = Sale::where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum('total') - $returns['amount'];

        $purchaseCost = Purchase::where('branch_id', $branchId)
            ->whereBetween('purchase_date', [$from, $to])
            ->sum('total');

        $totalExpenses = Expense::where('branch_id', $branchId)
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        $totalDiscounts = Sale::where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum('discount_amount');

        // True cost of goods sold for the period (captured per sale line at sale time),
        // less the COGS reversed by returns.
        $cogs = SaleItem::whereHas('sale', fn($q) => $q->where('branch_id', $branchId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
            ->sum('cost') - $returns['cogs'];

        $grossProfit = $salesRevenue - $cogs;
        $netProfit   = $grossProfit - $totalExpenses;

        // Daily chart data
        $chartData = Sale::where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top products (gross sales, netted per-product by returns in the period)
        $retByProduct = $this->returnsByProduct($branchId, $from, $to);
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(quantity) as qty, SUM(subtotal) as revenue, SUM(cost) as cogs'))
            ->whereHas('sale', fn($q) => $q->where('branch_id', $branchId)
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
        $branchId    = auth()->user()->branch_id;

        $sales = Sale::with(['customer', 'user'])
            ->where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->paginate(20);

        $totals = Sale::where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('COUNT(*) as count, SUM(total) as total, SUM(paid_amount) as paid, SUM(discount_amount) as discount')
            ->first();

        // Sales stay immutable; surface returns and the net separately.
        $returnAmount = $this->returnTotals($branchId, $from, $to)['amount'];
        $netTotal     = (float) ($totals->total ?? 0) - $returnAmount;

        $byPaymentMethod = Sale::where('branch_id', $branchId)
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
            ->whereHas('sale', fn($q) => $q->where('branch_id', $branchId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
            ->with('product.category')
            ->groupBy('products.category_id')
            ->orderByDesc('revenue')
            ->get();

        // Net each category by returned revenue in the period.
        $returnByCategory = SaleReturnItem::join('products', 'sale_return_items.product_id', '=', 'products.id')
            ->whereHas('saleReturn', fn($q) => $q
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->whereHas('sale', fn($s) => $s->where('branch_id', $branchId)))
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
        $branchId = auth()->user()->branch_id;

        $stocks = Stock::with(['product.category', 'product.brand'])
            ->where('branch_id', $branchId)
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
        $branchId = auth()->user()->branch_id;

        $alerts = Product::with(['category', 'stocks' => fn($q) => $q->where('branch_id', $branchId)])
            ->whereHas('stocks', fn($q) => $q->where('branch_id', $branchId)
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
        $branchId    = auth()->user()->branch_id;

        $retByProduct = $this->returnsByProduct($branchId, $from, $to);

        $products = SaleItem::select(
                'product_id',
                DB::raw('SUM(quantity) as qty_sold'),
                DB::raw('SUM(subtotal) as revenue'),
                DB::raw('SUM(cost) as cost')
            )
            ->whereHas('sale', fn($q) => $q->where('branch_id', $branchId)
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
        $branchId    = auth()->user()->branch_id;

        $payments = Payment::with(['account', 'sale', 'purchase'])
            ->whereHas('account', fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(20);

        $totals = [
            'in'  => Payment::whereHas('account', fn($q) => $q->where('branch_id', $branchId))->where('type', 'payment_in')->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->sum('amount'),
            'out' => Payment::whereHas('account', fn($q) => $q->where('branch_id', $branchId))->where('type', 'payment_out')->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->sum('amount'),
        ];

        return view('reports.payments', compact('payments', 'totals', 'from', 'to'));
    }

    // Expense Report
    public function expenses(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId    = auth()->user()->branch_id;

        $expenses = Expense::with(['category', 'account'])
            ->where('branch_id', $branchId)
            ->whereBetween('expense_date', [$from, $to])
            ->latest('expense_date')
            ->paginate(20);

        $byCategory = Expense::where('branch_id', $branchId)
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
        $branchId    = auth()->user()->branch_id;

        $cashiers = Sale::where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('user_id, COUNT(*) as sale_count, SUM(total) as total, SUM(paid_amount) as collected')
            ->with('user:id,name')
            ->groupBy('user_id')
            ->get();

        return view('reports.user_reports', compact('cashiers', 'from', 'to'));
    }

    // Export PDF or Excel
    public function export(Request $request, string $type)
    {
        [$from, $to] = $this->dateRange($request);
        $format      = $request->format ?? 'pdf';
        $branchId    = auth()->user()->branch_id;

        // Build data based on type
        $data = match($type) {
            'sales'    => Sale::where('branch_id', $branchId)->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->with('customer')->get(),
            'stock'    => Stock::where('branch_id', $branchId)->with('product.category')->get(),
            'expenses' => Expense::where('branch_id', $branchId)->whereBetween('expense_date', [$from, $to])->with('category')->get(),
            default    => collect(),
        };

        if ($format === 'pdf') {
            $pdf = Pdf::loadView("reports.export.{$type}_pdf", compact('data', 'from', 'to'))->setPaper('A4');
            return $pdf->download("{$type}-report-{$from}-to-{$to}.pdf");
        }

        // Excel export via Maatwebsite
        return Excel::download(new \App\Exports\ReportExport($data, $type), "{$type}-{$from}-{$to}.xlsx");
    }
}
