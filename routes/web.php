<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\HRM\StaffController;
use App\Http\Controllers\HRM\AttendanceController;
use App\Http\Controllers\HRM\LeaveController;
use App\Http\Controllers\HRM\PayrollController;
use App\Http\Controllers\HRM\HolidayController;
use App\Http\Controllers\HRM\AppreciationController;
use App\Http\Controllers\OnlineOrderController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\PosController;


// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // POS Screen
    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    Route::post('/pos/sale', [PosController::class, 'storeSale'])->name('pos.sale');
    Route::get('/pos/products/search', [PosController::class, 'searchProducts'])->name('pos.products.search');
    Route::get('/pos/products/barcode/{barcode}', [PosController::class, 'findByBarcode'])->name('pos.barcode');
    Route::get('/pos/receipt/{id}', [PosController::class, 'receipt'])->name('pos.receipt');

    // Products
    Route::resource('products', ProductController::class);
    Route::get('/products/{product}/print-barcode', [BarcodeController::class, 'print'])->name('products.barcode');
    Route::post('/barcodes/bulk-print', [BarcodeController::class, 'bulkPrint'])->name('barcodes.bulk');

    // Product sub-modules
    Route::resource('brands', BrandController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
    Route::resource('variations', VariationController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);

    // Stock
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/adjustments', [StockController::class, 'adjustments'])->name('stock.adjustments');
    Route::post('/stock/adjustments', [StockController::class, 'storeAdjustment'])->name('stock.adjustments.store');
    Route::get('/stock/transfers', [StockController::class, 'transfers'])->name('stock.transfers');
    Route::post('/stock/transfers', [StockController::class, 'storeTransfer'])->name('stock.transfers.store');
    Route::patch('/stock/transfers/{id}/status', [StockController::class, 'updateTransferStatus']);

    // Sales
    Route::resource('sales', SaleController::class);
    Route::get('/sales/{id}/invoice', [SaleController::class, 'invoice'])->name('sales.invoice');
    Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');

    // Sales Returns
    Route::resource('sale-returns', SaleReturnController::class);

    // Payment In
    Route::get('/payments-in', [PaymentController::class, 'indexIn'])->name('payments.in');
    Route::post('/payments-in', [PaymentController::class, 'storeIn'])->name('payments.in.store');

    // Quotations
    Route::resource('quotations', QuotationController::class);
    Route::post('/quotations/{id}/convert', [QuotationController::class, 'convertToSale'])->name('quotations.convert');
    Route::get('/quotations/{id}/pdf', [QuotationController::class, 'pdf'])->name('quotations.pdf');

    // Purchases
    Route::resource('purchases', PurchaseController::class);
    Route::get('/purchases/{id}/bill', [PurchaseController::class, 'bill'])->name('purchases.bill');

    // Purchase Returns
    Route::resource('purchase-returns', PurchaseReturnController::class);

    // Payment Out
    Route::get('/payments-out', [PaymentController::class, 'indexOut'])->name('payments.out');
    Route::post('/payments-out', [PaymentController::class, 'storeOut'])->name('payments.out.store');

    // Parties
    Route::resource('customers', CustomerController::class);
    Route::get('/customers/{id}/ledger', [CustomerController::class, 'ledger'])->name('customers.ledger');
    Route::resource('suppliers', SupplierController::class);
    Route::get('/suppliers/{id}/ledger', [SupplierController::class, 'ledger'])->name('suppliers.ledger');

    // Cash & Bank
    Route::resource('accounts', AccountController::class);
    Route::get('/accounts/{id}/transactions', [AccountController::class, 'transactions'])->name('accounts.transactions');
    Route::post('/accounts/transfer', [AccountController::class, 'transfer'])->name('accounts.transfer');

    // Expenses
    Route::resource('expense-categories', ExpenseCategoryController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
    Route::resource('expenses', ExpenseController::class);

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit_loss');
        Route::get('/sales-summary', [ReportController::class, 'salesSummary'])->name('sales_summary');
        Route::get('/stock-summary', [ReportController::class, 'stockSummary'])->name('stock_summary');
        Route::get('/stock-alert', [ReportController::class, 'stockAlert'])->name('stock_alert');
        Route::get('/rate-list', [ReportController::class, 'rateList'])->name('rate_list');
        Route::get('/product-sales', [ReportController::class, 'productSales'])->name('product_sales');
        Route::get('/payments', [ReportController::class, 'payments'])->name('payments');
        Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
        Route::get('/user-reports', [ReportController::class, 'userReports'])->name('user_reports');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
    });

    // HRM
    Route::prefix('hrm')->name('hrm.')->group(function () {
        Route::get('/', [StaffController::class, 'dashboard'])->name('dashboard');
        Route::resource('staff', StaffController::class);
        Route::resource('attendance', AttendanceController::class);
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check_in');
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check_out');
        Route::resource('leaves', LeaveController::class);
        Route::patch('/leaves/{id}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::patch('/leaves/{id}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
        Route::resource('payroll', PayrollController::class);
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::resource('holidays', HolidayController::class);
        Route::resource('appreciations', AppreciationController::class);
    });

    // Online Orders
    Route::resource('online-orders', OnlineOrderController::class);
    Route::patch('/online-orders/{id}/status', [OnlineOrderController::class, 'updateStatus'])->name('online-orders.status');
    Route::post('/online-orders/{id}/convert-to-sale', [OnlineOrderController::class, 'convertToSale']);

    // Website Setup
    Route::get('/website', [WebsiteController::class, 'index'])->name('website.index');
    Route::post('/website/settings', [WebsiteController::class, 'saveSettings'])->name('website.settings');
    Route::resource('website/banners', BannerController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'save'])->name('settings.save');
    // Route::resource('/settings/branches', SettingController::class . 'BranchController');
    // Route::resource('/settings/counters', SettingController::class . 'CounterController');
    // Route::resource('/settings/users', SettingController::class . 'UserController');

    // API endpoints (for POS screen AJAX)
    Route::prefix('api')->group(function () {
        Route::get('/products/search', [ProductController::class, 'apiSearch']);
        Route::get('/products/{id}', [ProductController::class, 'apiShow']);
        Route::get('/customers/search', [CustomerController::class, 'apiSearch']);
        Route::get('/stock/{product_id}/{branch_id}', [StockController::class, 'apiGetStock']);
        Route::get('/dashboard/stats', [DashboardController::class, 'apiStats']);
    });
});
