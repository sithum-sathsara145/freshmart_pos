# FreshMart POS — Setup Guide
PHP + Laravel + MySQL

---

## 1. Requirements
- PHP 8.3+
- MySQL 8+
- Composer
- Node.js 18+

---

## 2. Install Laravel project

```bash
composer create-project laravel/laravel freshmart-pos
cd freshmart-pos
```

---

## 3. Install packages

```bash
# Auth + permissions
composer require laravel/sanctum
composer require spatie/laravel-permission

# PDF generation (invoices)
composer require barryvdh/laravel-dompdf

# Barcode generation
composer require picqer/php-barcode-generator

# Excel export (reports)
composer require maatwebsite/excel

# Image handling (product photos)
composer require intervention/image
```

---

## 4. Configure .env

```env
APP_NAME="FreshMart POS"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=freshmart_pos
DB_USERNAME=root
DB_PASSWORD=your_password

# For file uploads
FILESYSTEM_DISK=public
```

---

## 5. Run migrations + seed

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE freshmart_pos CHARACTER SET utf8mb4;"

# Run the schema SQL directly
mysql -u root -p freshmart_pos < database_schema.sql

# Or use Laravel migrations (recommended)
php artisan migrate
php artisan db:seed

# Storage link for uploads
php artisan storage:link
```

---

## 6. File structure

```
freshmart-pos/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── PosController.php          ← POS billing
│   │   │   ├── DashboardController.php
│   │   │   ├── ProductController.php
│   │   │   ├── SaleController.php
│   │   │   ├── PurchaseController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── StockController.php
│   │   │   ├── AccountController.php
│   │   │   ├── ReportController.php
│   │   │   ├── BarcodeController.php
│   │   │   └── HRM/
│   │   │       ├── StaffController.php
│   │   │       ├── AttendanceController.php
│   │   │       ├── LeaveController.php
│   │   │       ├── PayrollController.php
│   │   │       └── HolidayController.php
│   │   └── Middleware/
│   │       ├── CheckPermission.php
│   │       └── SetBranch.php
│   └── Models/
│       ├── Product.php                    ← Done
│       ├── Sale.php
│       ├── SaleItem.php
│       ├── SaleReturn.php
│       ├── Purchase.php
│       ├── PurchaseItem.php
│       ├── Customer.php
│       ├── Supplier.php
│       ├── Stock.php
│       ├── Account.php
│       ├── Payment.php
│       ├── Expense.php
│       ├── Branch.php
│       ├── Counter.php
│       ├── Staff.php
│       ├── Attendance.php
│       └── Payroll.php
├── resources/views/
│   ├── layouts/
│   │   └── app.blade.php                 ← Done (sidebar + layout)
│   ├── pos/
│   │   ├── index.blade.php               ← Done (POS screen)
│   │   └── receipt.blade.php
│   ├── dashboard/
│   ├── products/
│   ├── sales/
│   ├── purchases/
│   ├── reports/
│   └── hrm/
├── routes/
│   └── web.php                           ← Done (all routes)
├── database/
│   └── database_schema.sql               ← Done (all tables)
└── public/
```

---

## 7. Roles to seed

```php
// In DatabaseSeeder.php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

Role::create(['name' => 'super_admin']);
Role::create(['name' => 'manager']);
Role::create(['name' => 'cashier']);
Role::create(['name' => 'stock_manager']);

// Permissions
$permissions = [
    'pos.access', 'sales.view', 'sales.create', 'sales.edit', 'sales.delete',
    'purchases.view', 'purchases.create', 'purchases.edit',
    'products.view', 'products.create', 'products.edit', 'products.delete',
    'reports.view', 'hrm.view', 'settings.access', 'cash.view',
];

foreach ($permissions as $perm) {
    Permission::create(['name' => $perm]);
}

// Super admin gets all
Role::findByName('super_admin')->givePermissionTo(Permission::all());

// Cashier gets POS + sales only
Role::findByName('cashier')->givePermissionTo(['pos.access', 'sales.view', 'sales.create']);
```

---

## 8. Start development server

```bash
php artisan serve
# Visit: http://localhost:8000
```

---

## 9. Deployment (production)

### DigitalOcean / Hetzner (recommended for Sri Lanka)
- Ubuntu 24.04 VPS
- Nginx + PHP-FPM + MySQL
- SSL with Let's Encrypt (free)
- ~$6–12/month

```bash
# On server
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

---

## 10. Next steps to build

1. `DashboardController` — summary stats query
2. `ProductController` — full CRUD with image upload
3. `SaleController` — invoice generation + PDF
4. `ReportController` — P&L calculation
5. `BarcodeController` — generate + print labels
6. Receipt Blade view — thermal print layout
7. HRM controllers — attendance + payroll calc
8. Online order API endpoints
9. Website frontend (separate Blade/Livewire app)
