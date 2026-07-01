<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
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

    // Profit & Loss
    public function profitLoss(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId    = auth()->user()->branch_id;

        $salesRevenue = Sale::where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->where('status', '!=', 'returned')
            ->sum('total');

        $purchaseCost = Purchase::where('branch_id', $branchId)
            ->whereBetween('purchase_date', [$from, $to])
            ->sum('total');

        $totalExpenses = Expense::where('branch_id', $branchId)
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        $totalDiscounts = Sale::where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum('discount_amount');

        // True cost of goods sold for the period (captured per sale line at sale time).
        $cogs = SaleItem::whereHas('sale', fn($q) => $q->where('branch_id', $branchId)
                ->where('status', '!=', 'returned')
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
            ->sum('cost');

        $grossProfit = $salesRevenue - $cogs;
        $netProfit   = $grossProfit - $totalExpenses;

        // Daily chart data
        $chartData = Sale::where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top products
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(quantity) as qty, SUM(subtotal) as revenue, SUM(cost) as cogs'))
            ->whereHas('sale', fn($q) => $q->where('branch_id', $branchId)
                ->where('status', '!=', 'returned')
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'name'    => $item->product?->name,
                'qty'     => $item->qty,
                'revenue' => $item->revenue,
                'profit'  => $item->revenue - $item->cogs,
            ]);

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

        $byPaymentMethod = Sale::where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get();

        $byCategory = SaleItem::select('products.category_id', DB::raw('SUM(sale_items.subtotal) as revenue'))
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereHas('sale', fn($q) => $q->where('branch_id', $branchId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
            ->with('product.category')
            ->groupBy('products.category_id')
            ->orderByDesc('revenue')
            ->get();

        return view('reports.sales_summary', compact(
            'sales', 'totals', 'byPaymentMethod', 'byCategory', 'from', 'to'
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

        $products = SaleItem::select(
                'product_id',
                DB::raw('SUM(quantity) as qty_sold'),
                DB::raw('SUM(subtotal) as revenue')
            )
            ->whereHas('sale', fn($q) => $q->where('branch_id', $branchId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->where('status', '!=', 'returned'))
            ->with('product:id,name,purchase_price')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->paginate(20);

        return view('reports.product_sales', compact('products', 'from', 'to'));
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
