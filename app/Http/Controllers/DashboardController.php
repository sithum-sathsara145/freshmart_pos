<?php

namespace App\Http\Controllers;

use App\Support\CurrentBranch;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Staff;
use App\Models\Attendance;
use App\Models\OnlineOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $branchId = CurrentBranch::id();
        $today    = today();

        // Today's sales
        $todaySales = Sale::whereBranch($branchId)
            ->whereDate('created_at', $today)
            ->selectRaw('COUNT(*) as count, SUM(total) as total, SUM(paid_amount) as paid')
            ->first();

        // This month sales
        $monthSales = Sale::whereBranch($branchId)
            ->whereMonth('created_at', $today->month)
            ->whereYear('created_at', $today->year)
            ->selectRaw('COUNT(*) as count, SUM(total) as total')
            ->first();

        // Sales are immutable — net revenue cards by returns recorded in the same window.
        // (use fresh today()/now() so we don't depend on $today, which gets mutated below)
        $returnsToday = SaleReturn::whereHas('sale', fn($q) => $q->whereBranch($branchId))
            ->whereDate('created_at', today())->sum('return_amount');
        $returnsMonth = SaleReturn::whereHas('sale', fn($q) => $q->whereBranch($branchId))
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('return_amount');
        if ($todaySales) $todaySales->total = (float) ($todaySales->total ?? 0) - (float) $returnsToday;
        if ($monthSales) $monthSales->total = (float) ($monthSales->total ?? 0) - (float) $returnsMonth;

        // Yesterday sales (for % change)
        $yesterdaySales = Sale::whereBranch($branchId)
            ->whereDate('created_at', $today->subDay())
            ->sum('total');

        // Low stock items
        $lowStockCount = Stock::whereBranch($branchId)
            ->whereRaw('quantity < (SELECT min_stock FROM products WHERE products.id = stock.product_id)')
            ->count();

        // Out of stock
        $outOfStockCount = Stock::whereBranch($branchId)
            ->where('quantity', '<=', 0)
            ->count();

        // Staff on duty today
        $staffOnDuty = Attendance::whereDate('date', $today)
            ->where('status', 'present')
            ->count();

        $totalStaff = Staff::whereBranch($branchId)
            ->where('status', 'active')
            ->count();

        // Today's expenses
        $todayExpenses = Expense::whereBranch($branchId)
            ->whereDate('expense_date', $today)
            ->sum('amount');

        // Recent sales (last 8)
        $recentSales = Sale::with(['customer', 'user'])
            ->whereBranch($branchId)
            ->latest()
            ->limit(8)
            ->get();

        // Low stock products
        $lowStockProducts = Product::with(['category'])
            ->whereHas('stocks', function ($q) use ($branchId) {
                $q->whereBranch($branchId)
                  ->whereRaw('quantity < products.min_stock')
                  ->where('quantity', '>', 0);
            })
            ->limit(6)
            ->get()
            ->map(function ($p) use ($branchId) {
                $p->current_stock = $p->stockForBranch($branchId);
                return $p;
            });

        // Daily sales chart (last 7 days)
        $chartData = Sale::whereBranch($branchId)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, SUM(total) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Online orders pending
        $pendingOnlineOrders = OnlineOrder::whereBranch($branchId)
            ->where('status', 'new')
            ->count();

        // Top products today
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(quantity) as qty_sold, SUM(subtotal) as revenue'))
            ->whereHas('sale', fn($q) => $q->whereBranch($branchId)->whereDate('created_at', $today))
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'todaySales', 'monthSales', 'yesterdaySales',
            'lowStockCount', 'outOfStockCount',
            'staffOnDuty', 'totalStaff',
            'todayExpenses', 'recentSales',
            'lowStockProducts', 'chartData',
            'pendingOnlineOrders', 'topProducts'
        ));
    }

    // AJAX — live stats for dashboard refresh
    public function apiStats()
    {
        $branchId = CurrentBranch::id();
        $today    = today();

        return response()->json([
            'today_sales'    => Sale::whereBranch($branchId)->whereDate('created_at', $today)->sum('total')
                                - SaleReturn::whereHas('sale', fn($q) => $q->whereBranch($branchId))->whereDate('created_at', $today)->sum('return_amount'),
            'today_count'    => Sale::whereBranch($branchId)->whereDate('created_at', $today)->count(),
            'low_stock'      => Stock::whereBranch($branchId)->whereRaw('quantity < (SELECT min_stock FROM products WHERE products.id = stock.product_id)')->count(),
            'staff_on_duty'  => Attendance::whereDate('date', $today)->where('status', 'present')->count(),
            'pending_orders' => OnlineOrder::whereBranch($branchId)->where('status', 'new')->count(),
        ]);
    }
}
