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
use App\Http\Controllers\CounterSessionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\HRM\StaffController;
use App\Http\Controllers\HRM\AttendanceController;
use App\Http\Controllers\HRM\LeaveController;
use App\Http\Controllers\HRM\PayrollController;
use App\Http\Controllers\HRM\HolidayController;
use App\Http\Controllers\HRM\SelfServiceController;
use App\Http\Controllers\OnlineOrderController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\CreditDocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;


// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Signed, unauthenticated credit-document upload from the cashier's phone (opened via QR).
// The signature makes the URL a bearer token; the page also demands a one-time code or
// the cashier's password before the photo is accepted.
Route::middleware('signed')->group(function () {
    Route::get('/credit-upload/{sale}', [CreditDocumentController::class, 'phoneForm'])->name('pos.credit.upload.form');
    Route::post('/credit-upload/{sale}', [CreditDocumentController::class, 'storeFromPhone'])->name('pos.credit.upload.store');
});

// Protected routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

    // Working-branch switcher (all-branch users only; CurrentBranch::set() enforces that)
    Route::post('/branch/switch', function (\Illuminate\Http\Request $request) {
        $id = $request->input('branch_id');
        $ok = \App\Support\CurrentBranch::set($id === '' || $id === null ? null : (int) $id);

        return $ok
            ? back()->with('success', 'Now working in: '.\App\Support\CurrentBranch::name())
            : back()->withErrors(['branch' => 'You cannot switch to that branch.']);
    })->middleware('permission:branches.view_all')->name('branch.switch');

    // POS Screen
    Route::middleware('permission:pos.access')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos');
        Route::post('/pos/sale', [PosController::class, 'storeSale'])->name('pos.sale');
        // Held / parked bills
        Route::post('/pos/hold', [PosController::class, 'holdBill'])->name('pos.hold');
        Route::get('/pos/held', [PosController::class, 'heldBills'])->name('pos.held');
        Route::post('/pos/held/{id}/resume', [PosController::class, 'resumeHeld'])->name('pos.held.resume');
        Route::delete('/pos/held/{id}', [PosController::class, 'discardHeld'])->name('pos.held.discard');
        Route::get('/pos/products/search', [PosController::class, 'searchProducts'])->name('pos.products.search');
        Route::get('/pos/products/barcode/{barcode}', [PosController::class, 'findByBarcode'])->name('pos.barcode');
        Route::get('/pos/receipt/{id}', [PosController::class, 'receipt'])->name('pos.receipt');
    });
    // Credit-sale signed-document evidence: request a phone QR link, upload from the POS
    // webcam, or poll for arrival. Usable both from the POS counter and later from the
    // back office (customer page), so it's gated by either POS access or sales viewing.
    Route::middleware('permission:pos.access|sales.view')->group(function () {
        Route::get('/pos/sale/{sale}/credit-upload-link', [CreditDocumentController::class, 'link'])->name('pos.credit.link');
        Route::post('/pos/sale/{sale}/credit-document', [CreditDocumentController::class, 'storeFromCounter'])->name('pos.credit.counter');
        Route::get('/pos/sale/{sale}/credit-document', [CreditDocumentController::class, 'status'])->name('pos.credit.status');
    });
    Route::middleware('permission:pos.counter')->group(function () {
        Route::post('/pos/counter/open', [PosController::class, 'openCounter'])->name('pos.counter.open');
        Route::post('/pos/counter/close', [PosController::class, 'closeCounter'])->name('pos.counter.close');
    });

    // Products
    // Must be declared before the resource so they aren't captured by /products/{product}.
    Route::get('/products/upload-signature', [ProductController::class, 'uploadSignature'])->middleware('permission:products.create|products.edit')->name('products.upload-signature');
    Route::get('/products/import', [ProductController::class, 'importForm'])->middleware('permission:products.import')->name('products.import');
    Route::get('/products/import/sample', [ProductController::class, 'importSample'])->middleware('permission:products.import')->name('products.import.sample');
    Route::post('/products/import', [ProductController::class, 'import'])->middleware('permission:products.import')->name('products.import.store');
    Route::get('/products/export', [ProductController::class, 'export'])->middleware('permission:products.export')->name('products.export');
    Route::post('/products/quick', [ProductController::class, 'quickStore'])->middleware('permission:products.create')->name('products.quick');
    Route::post('/products/bulk-delete', [ProductController::class, 'bulkDestroy'])->middleware('permission:products.delete')->name('products.bulk-delete');
    Route::resource('products', ProductController::class)
        ->middlewareFor(['index', 'show'], 'permission:products.view')
        ->middlewareFor(['create', 'store'], 'permission:products.create')
        ->middlewareFor(['edit', 'update'], 'permission:products.edit')
        ->middlewareFor('destroy', 'permission:products.delete');

    // Barcodes
    Route::middleware('permission:barcodes.print')->group(function () {
        Route::get('/products/{product}/print-barcode', [BarcodeController::class, 'print'])->name('products.barcode');
        Route::get('/barcodes/labels', [BarcodeController::class, 'labels'])->name('barcodes.labels');
        Route::post('/barcodes/bulk-print', [BarcodeController::class, 'bulkPrint'])->name('barcodes.bulk');
    });

    // Product sub-modules
    Route::resource('brands', BrandController::class)->only(['index', 'store', 'edit', 'update', 'destroy'])->middleware('permission:catalog.brands.manage');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'edit', 'update', 'destroy'])->middleware('permission:catalog.categories.manage');
    Route::resource('variations', VariationController::class)->only(['index', 'store', 'edit', 'update', 'destroy'])->middleware('permission:catalog.variations.manage');

    // Stock
    Route::get('/stock', [StockController::class, 'index'])->middleware('permission:stock.view')->name('stock.index');
    Route::get('/stock/adjustments', [StockController::class, 'adjustments'])->middleware('permission:stock.adjust')->name('stock.adjustments');
    Route::post('/stock/adjustments', [StockController::class, 'storeAdjustment'])->middleware('permission:stock.adjust')->name('stock.adjustments.store');
    Route::get('/stock/transfers', [StockController::class, 'transfers'])->middleware('permission:stock.transfer')->name('stock.transfers');
    Route::post('/stock/transfers', [StockController::class, 'storeTransfer'])->middleware('permission:stock.transfer')->name('stock.transfers.store');
    Route::patch('/stock/transfers/{id}/status', [StockController::class, 'updateTransferStatus'])->middleware('permission:stock.transfer');

    // Sales (finalized once created — correct via Void/Return, collect payment via payments-in)
    Route::resource('sales', SaleController::class)->except(['edit', 'update'])
        ->middlewareFor(['index', 'show'], 'permission:sales.view')
        ->middlewareFor(['create', 'store'], 'permission:sales.create')
        ->middlewareFor('destroy', 'permission:sales.delete');
    Route::get('/sales/{id}/invoice', [SaleController::class, 'invoice'])->middleware('permission:sales.print')->name('sales.invoice');
    Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt'])->middleware('permission:sales.print')->name('sales.receipt');

    // Sales Returns (immutable once issued — no edit/update, delete reverses)
    Route::resource('sale-returns', SaleReturnController::class)->except(['edit', 'update'])
        ->middlewareFor(['index', 'show'], 'permission:sale_returns.view')
        ->middlewareFor(['create', 'store'], 'permission:sale_returns.create')
        ->middlewareFor('destroy', 'permission:sale_returns.reverse');

    // Payment In
    Route::get('/payments-in', [PaymentController::class, 'indexIn'])->middleware('permission:payments.in.view')->name('payments.in');
    Route::post('/payments-in', [PaymentController::class, 'storeIn'])->middleware('permission:payments.in.create')->name('payments.in.store');

    // Quotations (no in-place editor — delete + recreate, or convert to a sale)
    Route::resource('quotations', QuotationController::class)->except(['edit', 'update'])
        ->middlewareFor(['index', 'show'], 'permission:quotations.view')
        ->middlewareFor(['create', 'store'], 'permission:quotations.create')
        ->middlewareFor('destroy', 'permission:quotations.delete');
    Route::post('/quotations/{id}/convert', [QuotationController::class, 'convertToSale'])->middleware('permission:quotations.convert')->name('quotations.convert');
    Route::get('/quotations/{id}/pdf', [QuotationController::class, 'pdf'])->middleware('permission:quotations.view')->name('quotations.pdf');

    // Purchases
    Route::resource('purchases', PurchaseController::class)
        ->middlewareFor(['index', 'show'], 'permission:purchases.view')
        ->middlewareFor(['create', 'store'], 'permission:purchases.create')
        ->middlewareFor(['edit', 'update'], 'permission:purchases.edit')
        ->middlewareFor('destroy', 'permission:purchases.delete');
    Route::get('/purchases/{id}/bill', [PurchaseController::class, 'bill'])->middleware('permission:purchases.view')->name('purchases.bill');

    // Purchase Returns
    Route::resource('purchase-returns', PurchaseReturnController::class)->except(['edit', 'update'])
        ->middlewareFor(['index', 'show'], 'permission:purchase_returns.view')
        ->middlewareFor(['create', 'store'], 'permission:purchase_returns.create')
        ->middlewareFor('destroy', 'permission:purchase_returns.reverse');

    // Payment Out
    Route::get('/payments-out', [PaymentController::class, 'indexOut'])->middleware('permission:payments.out.view')->name('payments.out');
    Route::post('/payments-out', [PaymentController::class, 'storeOut'])->middleware('permission:payments.out.create')->name('payments.out.store');

    // Parties (ledger views were never built — use show instead)
    Route::resource('customers', CustomerController::class)
        ->middlewareFor(['index', 'show'], 'permission:customers.view')
        ->middlewareFor(['create', 'store'], 'permission:customers.create')
        ->middlewareFor(['edit', 'update'], 'permission:customers.edit')
        ->middlewareFor('destroy', 'permission:customers.delete');
    Route::resource('suppliers', SupplierController::class)
        ->middlewareFor(['index', 'show'], 'permission:suppliers.view')
        ->middlewareFor(['create', 'store'], 'permission:suppliers.create')
        ->middlewareFor(['edit', 'update'], 'permission:suppliers.edit')
        ->middlewareFor('destroy', 'permission:suppliers.delete');

    // Cash & Bank (no per-account show/edit page — manage from the index)
    Route::resource('accounts', AccountController::class)->only(['index', 'create', 'store', 'destroy'])
        ->middlewareFor('index', 'permission:accounts.view')
        ->middlewareFor(['create', 'store', 'destroy'], 'permission:accounts.manage');
    Route::get('/accounts/{id}/transactions', [AccountController::class, 'transactions'])->middleware('permission:accounts.view')->name('accounts.transactions');
    Route::post('/accounts/transfer', [AccountController::class, 'transfer'])->middleware('permission:accounts.transfer')->name('accounts.transfer');

    // Expenses
    Route::resource('expense-categories', ExpenseCategoryController::class)->only(['index', 'store', 'edit', 'update', 'destroy'])->middleware('permission:expenses.categories');
    Route::resource('expenses', ExpenseController::class)->except(['show'])
        ->middlewareFor('index', 'permission:expenses.view')
        ->middlewareFor(['create', 'store'], 'permission:expenses.create')
        ->middlewareFor(['edit', 'update'], 'permission:expenses.edit')
        ->middlewareFor('destroy', 'permission:expenses.delete');

    // Counter cash sessions (open/close history)
    Route::resource('counter-sessions', CounterSessionController::class)->only(['index', 'show'])->middleware('permission:counter_sessions.view');

    // Reports  (profit/cost figures are gated separately from plain reports)
    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->middleware('permission:reports.profit')->name('profit_loss');
        Route::get('/sales-summary', [ReportController::class, 'salesSummary'])->name('sales_summary');
        Route::get('/stock-summary', [ReportController::class, 'stockSummary'])->name('stock_summary');
        Route::get('/stock-alert', [ReportController::class, 'stockAlert'])->name('stock_alert');
        Route::get('/rate-list', [ReportController::class, 'rateList'])->name('rate_list');
        Route::get('/product-sales', [ReportController::class, 'productSales'])->middleware('permission:reports.profit')->name('product_sales');
        Route::get('/payments', [ReportController::class, 'payments'])->name('payments');
        Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
        Route::get('/user-reports', [ReportController::class, 'userReports'])->name('user_reports');
        // HRM reports expose staff pay and attendance, so they need hrm.view on top
        // of reports.view — a stock manager can read reports but not payroll.
        Route::get('/hrm-attendance', [ReportController::class, 'hrmAttendance'])->middleware('permission:hrm.view')->name('hrm_attendance');
        Route::get('/hrm-payroll', [ReportController::class, 'hrmPayroll'])->middleware('permission:hrm.payroll.manage')->name('hrm_payroll');
        Route::get('/hrm-leave', [ReportController::class, 'hrmLeave'])->middleware('permission:hrm.view')->name('hrm_leave');
        Route::get('/export/{type}', [ReportController::class, 'export'])->middleware('permission:reports.export')->name('export');
    });

    // HRM
    Route::prefix('hrm')->name('hrm.')->middleware('permission:hrm.view')->group(function () {
        Route::get('/', [StaffController::class, 'dashboard'])->name('dashboard');
        Route::resource('staff', StaffController::class)
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:hrm.staff.manage');
        Route::put('/staff/{staff}/entitlements', [StaffController::class, 'updateEntitlements'])
            ->middleware('permission:hrm.staff.manage')->name('staff.entitlements');
        Route::resource('attendance', AttendanceController::class)->only(['index', 'store', 'edit', 'update', 'destroy'])
            ->middlewareFor(['store', 'edit', 'update', 'destroy'], 'permission:hrm.attendance.manage');
        Route::post('/attendance/bulk', [AttendanceController::class, 'bulk'])->middleware('permission:hrm.attendance.manage')->name('attendance.bulk');
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check_in');
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check_out');
        // Filing/deleting leave was gated only by hrm.view, so anyone who could read
        // the HRM area could file or delete leave for any employee.
        Route::resource('leaves', LeaveController::class)->only(['index', 'create', 'store', 'destroy'])
            ->middlewareFor(['create', 'store', 'destroy'], 'permission:hrm.leaves.request');
        Route::patch('/leaves/{id}/approve', [LeaveController::class, 'approve'])->middleware('permission:hrm.leaves.approve')->name('leaves.approve');
        Route::patch('/leaves/{id}/reject', [LeaveController::class, 'reject'])->middleware('permission:hrm.leaves.approve')->name('leaves.reject');
        Route::resource('payroll', PayrollController::class)->only(['index', 'update', 'destroy'])->middleware('permission:hrm.payroll.manage');
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])->middleware('permission:hrm.payroll.manage')->name('payroll.generate');
        Route::patch('/payroll/{payroll}/paid', [PayrollController::class, 'markPaid'])->middleware('permission:hrm.payroll.manage')->name('payroll.paid');
        Route::get('/payroll/{payroll}/payslip', [PayrollController::class, 'payslip'])->middleware('permission:hrm.payroll.manage')->name('payroll.payslip');
        Route::resource('holidays', HolidayController::class)->only(['index', 'store', 'destroy'])
            ->middlewareFor(['store', 'destroy'], 'permission:hrm.holidays.manage');
        // 'appreciations' module was never built (no views, no links) — route removed.
    });

    // My HR (self-service) — deliberately OUTSIDE the /hrm group above. Those
    // routes govern everyone's records and need hrm.view; these only ever expose
    // the signed-in user's own data, so a cashier reaches them without gaining
    // any access to the HRM management area.
    Route::prefix('my')->name('my.')->group(function () {
        Route::get('/', [SelfServiceController::class, 'index'])->middleware('permission:hrm.self.view')->name('index');
        Route::get('/attendance', [SelfServiceController::class, 'attendance'])->middleware('permission:hrm.self.view')->name('attendance');
        Route::get('/payslips', [SelfServiceController::class, 'payslips'])->middleware('permission:hrm.self.view')->name('payslips');
        Route::get('/payslips/{payroll}', [SelfServiceController::class, 'payslip'])->middleware('permission:hrm.self.view')->name('payslip');
        Route::get('/leave', [SelfServiceController::class, 'leave'])->middleware('permission:hrm.self.view')->name('leave');
        Route::post('/leave', [SelfServiceController::class, 'storeLeave'])->middleware('permission:hrm.self.leave')->name('leave.store');
        Route::delete('/leave/{leave}', [SelfServiceController::class, 'destroyLeave'])->middleware('permission:hrm.self.leave')->name('leave.destroy');
        Route::post('/attendance/check-in', [SelfServiceController::class, 'clockIn'])->middleware('permission:hrm.self.attendance')->name('checkin');
        Route::post('/attendance/check-out', [SelfServiceController::class, 'clockOut'])->middleware('permission:hrm.self.attendance')->name('checkout');
    });

    // Online Orders
    Route::resource('online-orders', OnlineOrderController::class)->only(['index', 'show', 'destroy'])
        ->middlewareFor(['index', 'show'], 'permission:online_orders.view')
        ->middlewareFor('destroy', 'permission:online_orders.manage');
    Route::patch('/online-orders/{id}/status', [OnlineOrderController::class, 'updateStatus'])->middleware('permission:online_orders.manage')->name('online-orders.status');
    Route::post('/online-orders/{id}/convert-to-sale', [OnlineOrderController::class, 'convertToSale'])->middleware('permission:online_orders.manage|sales.create');

    // Website Setup
    Route::middleware('permission:website.manage')->group(function () {
        Route::get('/website', [WebsiteController::class, 'index'])->name('website.index');
        Route::post('/website/settings', [WebsiteController::class, 'saveSettings'])->name('website.settings');
        // ->names(): a resource at 'website/banners' auto-names routes 'banners.*',
        // but every view + controller redirect calls route('website.banners.*').
        Route::resource('website/banners', BannerController::class)
            ->only(['index', 'store', 'edit', 'update', 'destroy'])
            ->names('website.banners');
    });

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->middleware('permission:settings.access')->name('settings.index');
    Route::post('/settings', [SettingController::class, 'save'])->middleware('permission:settings.access')->name('settings.save');
    Route::post('/settings/api-keys', [SettingController::class, 'saveApiKeys'])->middleware('permission:settings.api_keys')->name('settings.api-keys.save');

    // Users — the list lives on the Settings → Users tab; these are the writes.
    // (the policies also re-check rank on every one of these)
    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Roles & permissions
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view|roles.manage')->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.manage')->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.manage')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.manage')->name('roles.destroy');

    // API endpoints (for POS screen AJAX)
    // These have no route names, so they're gated by the data they expose.
    Route::prefix('api')->group(function () {
        Route::get('/products/search', [ProductController::class, 'apiSearch'])->middleware('permission:products.view|pos.access');
        Route::get('/products/{id}', [ProductController::class, 'apiShow'])->middleware('permission:products.view|pos.access');
        Route::get('/customers/search', [CustomerController::class, 'apiSearch'])->middleware('permission:customers.view');
        Route::post('/customers', [CustomerController::class, 'apiStore'])->middleware('permission:customers.create');
        Route::get('/stock/{product_id}/{branch_id}', [StockController::class, 'apiGetStock'])->middleware('permission:stock.view|pos.access');
        Route::get('/dashboard/stats', [DashboardController::class, 'apiStats'])->middleware('permission:dashboard.view');
    });
});
