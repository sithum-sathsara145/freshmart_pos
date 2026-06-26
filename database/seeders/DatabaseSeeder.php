<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Account;
use App\Models\Setting;
use App\Models\ExpenseCategory;
use App\Models\Coupon;
use App\Models\Staff;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // ── Branches ──────────────────────────────────────────
        $main = Branch::create(['name' => 'Main Branch — Colombo', 'city' => 'Colombo', 'phone' => '011-2345678', 'address' => 'No. 42, Main Street, Colombo 07', 'is_main' => true, 'status' => 'active']);
        $kandy = Branch::create(['name' => 'Branch 2 — Kandy', 'city' => 'Kandy', 'phone' => '081-2222333', 'is_main' => false, 'status' => 'active']);

        // ── Counters ──────────────────────────────────────────
        $counter1 = Counter::create(['branch_id' => $main->id, 'name' => 'Counter 1', 'status' => 'open', 'cash_balance' => 0]);
        Counter::create(['branch_id' => $main->id, 'name' => 'Counter 2', 'status' => 'closed', 'cash_balance' => 0]);
        Counter::create(['branch_id' => $kandy->id, 'name' => 'Counter 1', 'status' => 'open', 'cash_balance' => 0]);

        // ── Permissions ───────────────────────────────────────
        $permissions = [
            'pos.access',
            'dashboard.view',
            'sales.view', 'sales.create', 'sales.edit', 'sales.delete',
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'stock.view', 'stock.adjust', 'stock.transfer',
            'customers.view', 'customers.create', 'customers.edit',
            'suppliers.view', 'suppliers.create', 'suppliers.edit',
            'accounts.view', 'accounts.manage',
            'expenses.view', 'expenses.create',
            'reports.view',
            'hrm.view', 'hrm.manage',
            'settings.access',
            'online_orders.view', 'online_orders.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── Roles ─────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'dashboard.view', 'pos.access',
            'sales.view', 'sales.create', 'sales.edit',
            'purchases.view', 'purchases.create',
            'products.view', 'products.create', 'products.edit',
            'stock.view', 'stock.adjust',
            'customers.view', 'customers.create',
            'suppliers.view',
            'accounts.view',
            'expenses.view', 'expenses.create',
            'reports.view',
            'hrm.view',
            'online_orders.view', 'online_orders.manage',
        ]);

        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->givePermissionTo(['dashboard.view', 'pos.access', 'sales.view', 'sales.create', 'customers.view', 'customers.create', 'products.view']);

        $stockMgr = Role::firstOrCreate(['name' => 'stock_manager']);
        $stockMgr->givePermissionTo(['dashboard.view', 'products.view', 'products.create', 'products.edit', 'stock.view', 'stock.adjust', 'stock.transfer', 'purchases.view', 'purchases.create']);

        // ── Users ─────────────────────────────────────────────
        $admin = User::firstOrCreate(['email' => 'admin@freshmart.lk'], [
            'name' => 'Admin User', 'password' => Hash::make('admin123'), 'branch_id' => $main->id, 'counter_id' => $counter1->id, 'status' => 'active',
        ]);
        $admin->assignRole('super_admin');

        $mgr = User::firstOrCreate(['email' => 'manager@freshmart.lk'], [
            'name' => 'Sithara Perera', 'password' => Hash::make('admin123'), 'branch_id' => $main->id, 'status' => 'active',
        ]);
        $mgr->assignRole('manager');

        $cs = User::firstOrCreate(['email' => 'cashier@freshmart.lk'], [
            'name' => 'Nimal Kumara', 'password' => Hash::make('admin123'), 'branch_id' => $main->id, 'counter_id' => $counter1->id, 'status' => 'active',
        ]);
        $cs->assignRole('cashier');

        $sm = User::firstOrCreate(['email' => 'stock@freshmart.lk'], [
            'name' => 'Ruwan Jayakody', 'password' => Hash::make('admin123'), 'branch_id' => $main->id, 'status' => 'active',
        ]);
        $sm->assignRole('stock_manager');

        // ── Staff ─────────────────────────────────────────────
        Staff::insert([
            ['user_id' => $cs->id, 'branch_id' => $main->id, 'name' => 'Nimal Kumara', 'phone' => '077-1234567', 'role' => 'Cashier', 'basic_salary' => 28000, 'join_date' => '2024-01-15', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $mgr->id, 'branch_id' => $main->id, 'name' => 'Sithara Perera', 'phone' => '071-9876543', 'role' => 'Supervisor', 'basic_salary' => 42000, 'join_date' => '2023-03-10', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $sm->id, 'branch_id' => $main->id, 'name' => 'Ruwan Jayakody', 'phone' => '076-5554433', 'role' => 'Stock Manager', 'basic_salary' => 35000, 'join_date' => '2023-06-01', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => null, 'branch_id' => $main->id, 'name' => 'Amaya Mendis', 'phone' => '078-3321100', 'role' => 'Cashier', 'basic_salary' => 26000, 'join_date' => '2024-08-01', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Brands ────────────────────────────────────────────
        $brands = collect(['Anchor', 'Maliban', 'Nestlé', 'Keells', 'McCain', 'Milo', 'Farm Fresh', 'Coca-Cola'])
            ->mapWithKeys(fn($n) => [$n => Brand::create(['name' => $n])->id]);

        // ── Categories ───────────────────────────────────────
        $cats = collect(['Grocery', 'Beverages', 'Dairy', 'Bakery', 'Meat', 'Frozen', 'Personal Care', 'Household'])
            ->mapWithKeys(fn($n) => [$n => Category::create(['name' => $n])->id]);

        // ── Products ─────────────────────────────────────────
        $products = [
            ['name' => 'Anchor Milk 1L', 'barcode' => '8901234567890', 'cat' => 'Dairy', 'brand' => 'Anchor', 'buy' => 250, 'sell' => 290, 'stock' => 48, 'min' => 10],
            ['name' => 'Basmati Rice 1kg', 'barcode' => '4902430018937', 'cat' => 'Grocery', 'brand' => 'Keells', 'buy' => 480, 'sell' => 580, 'stock' => 120, 'min' => 20],
            ['name' => 'Coca-Cola 1.5L', 'barcode' => '5449000000996', 'cat' => 'Beverages', 'brand' => 'Coca-Cola', 'buy' => 320, 'sell' => 390, 'stock' => 60, 'min' => 12],
            ['name' => 'Chicken Breast 1kg', 'barcode' => '7612100055557', 'cat' => 'Meat', 'brand' => 'Keells', 'buy' => 1050, 'sell' => 1250, 'stock' => 8, 'min' => 20],
            ['name' => 'Eggs × 12', 'barcode' => '5010477348619', 'cat' => 'Dairy', 'brand' => 'Farm Fresh', 'buy' => 380, 'sell' => 460, 'stock' => 40, 'min' => 15],
            ['name' => 'Anchor Butter 200g', 'barcode' => '0012000001819', 'cat' => 'Dairy', 'brand' => 'Anchor', 'buy' => 270, 'sell' => 320, 'stock' => 22, 'min' => 8],
            ['name' => 'Milo 400g', 'barcode' => '4902430055284', 'cat' => 'Beverages', 'brand' => 'Milo', 'buy' => 680, 'sell' => 790, 'stock' => 6, 'min' => 12],
            ['name' => 'Maliban Cream Crackers', 'barcode' => '4890006100012', 'cat' => 'Bakery', 'brand' => 'Maliban', 'buy' => 180, 'sell' => 220, 'stock' => 35, 'min' => 10],
            ['name' => 'Frozen Peas 500g', 'barcode' => '5000116024735', 'cat' => 'Frozen', 'brand' => 'McCain', 'buy' => 155, 'sell' => 195, 'stock' => 50, 'min' => 10],
            ['name' => 'Nestlé Pure Life 1.5L', 'barcode' => '4005900021793', 'cat' => 'Beverages', 'brand' => 'Nestlé', 'buy' => 80, 'sell' => 95, 'stock' => 200, 'min' => 30],
        ];

        foreach ($products as $p) {
            $prod = Product::create([
                'name' => $p['name'], 'barcode' => $p['barcode'],
                'category_id' => $cats[$p['cat']], 'brand_id' => $brands[$p['brand']],
                'purchase_price' => $p['buy'], 'sale_price' => $p['sell'],
                'min_stock' => $p['min'], 'unit' => 'Piece', 'status' => 'active',
                'show_in_online_store' => true, 'created_by' => $admin->id,
            ]);
            Stock::create(['product_id' => $prod->id, 'branch_id' => $main->id, 'quantity' => $p['stock']]);
        }

        // ── Customers ─────────────────────────────────────────
        Customer::insert([
            ['name' => 'Nimal Silva', 'phone' => '077-1234567', 'email' => 'nimal@gmail.com', 'loyalty_points' => 1240, 'loyalty_level' => 'silver', 'total_purchases' => 48200, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kamani Perera', 'phone' => '071-9876543', 'email' => 'kamani@yahoo.com', 'loyalty_points' => 2840, 'loyalty_level' => 'gold', 'total_purchases' => 82400, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Saman Rathnayake', 'phone' => '078-5554433', 'email' => null, 'loyalty_points' => 420, 'loyalty_level' => 'bronze', 'total_purchases' => 12600, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dilini Mendis', 'phone' => '076-3321100', 'email' => 'dilini@gmail.com', 'loyalty_points' => 1920, 'loyalty_level' => 'silver', 'total_purchases' => 64800, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Suppliers ─────────────────────────────────────────
        Supplier::insert([
            ['name' => 'Keells Foods Pvt Ltd', 'contact_person' => 'Mr. Suresh', 'phone' => '011-2345678', 'city' => 'Colombo', 'total_purchases' => 820000, 'balance_due' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nestlé Lanka Ltd', 'contact_person' => 'Ms. Dilini', 'phone' => '011-5678901', 'city' => 'Colombo', 'total_purchases' => 540000, 'balance_due' => 62500, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Maliban Biscuit Co.', 'contact_person' => 'Mr. Kamal', 'phone' => '038-2244680', 'city' => 'Matugama', 'total_purchases' => 290000, 'balance_due' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Anchor (Fonterra)', 'contact_person' => 'Mr. Nishantha', 'phone' => '011-7654321', 'city' => 'Colombo', 'total_purchases' => 210000, 'balance_due' => 79600, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Accounts ─────────────────────────────────────────
        Account::insert([
            ['name' => 'Counter Cash', 'type' => 'cash', 'branch_id' => $main->id, 'balance' => 28400, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Store Safe', 'type' => 'cash', 'branch_id' => $main->id, 'balance' => 142000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sampath Bank', 'type' => 'bank', 'branch_id' => $main->id, 'account_number' => '00812345678', 'balance' => 840200, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'BOC Current', 'type' => 'bank', 'branch_id' => $kandy->id, 'account_number' => '00987654321', 'balance' => 220000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Expense categories ────────────────────────────────
        ExpenseCategory::insert([
            ['name' => 'Rent', 'description' => 'Monthly shop rental', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Utility', 'description' => 'Electricity, water, internet', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Staff', 'description' => 'Casual labour wages', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Maintenance', 'description' => 'Equipment repairs', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transport', 'description' => 'Delivery and logistics', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Marketing', 'description' => 'Ads and promotions', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Coupons ───────────────────────────────────────────
        Coupon::insert([
            ['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10, 'min_order_amount' => 500, 'max_uses' => null, 'used_count' => 142, 'expires_at' => '2026-12-31', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'FLAT500', 'type' => 'fixed', 'value' => 500, 'min_order_amount' => 2000, 'max_uses' => null, 'used_count' => 38, 'expires_at' => '2026-12-31', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'NEWCUST', 'type' => 'percentage', 'value' => 15, 'min_order_amount' => 0, 'max_uses' => 1, 'used_count' => 24, 'expires_at' => '2026-12-31', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Settings ─────────────────────────────────────────
        $settingsData = [
            'business_name'   => 'FreshMart Supermarket',
            'address'         => 'No. 42, Main Street, Colombo 07',
            'phone'           => '011-2345678',
            'email'           => 'info@freshmart.lk',
            'currency'        => 'LKR',
            'receipt_footer'  => 'Thank you! Visit again.',
            'receipt_template'=> 'thermal_58mm',
            'tax_enabled'     => '0',
            'loyalty_earn_rate' => '20',
            'store_name'      => 'FreshMart Online Store',
            'tagline'         => 'Fresh groceries delivered to your door',
            'announcement'    => 'Free delivery on orders over Rs. 2,000!',
            'enable_ordering' => '1',
        ];

        foreach ($settingsData as $key => $value) {
            Setting::firstOrCreate(['key_name' => $key], ['value' => $value]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ FreshMart POS seeded successfully!');
        $this->command->info('   Admin:   admin@freshmart.lk / admin123');
        $this->command->info('   Manager: manager@freshmart.lk / admin123');
        $this->command->info('   Cashier: cashier@freshmart.lk / admin123');
    }
}
