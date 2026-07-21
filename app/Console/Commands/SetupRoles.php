<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * One-off (idempotent) setup of the role hierarchy.
 *
 * Safe to re-run: it only ever adds/updates, never removes a role or a user.
 *
 *   php artisan roles:setup                      (uses defaults)
 *   php artisan roles:setup --dev-email=me@x.lk --dev-password=secret
 */
class SetupRoles extends Command
{
    protected $signature = 'roles:setup
        {--dev-email=dev@freshmart.lk : Email for the hidden super_admin developer account}
        {--dev-password= : Password for that account (defaults to the project convention)}';

    protected $description = 'Create the admin role, set role ranks, reconcile permission grants and seed the developer account';

    /** name => [level, label, is_system, description] */
    private const RANKS = [
        'super_admin'   => [100, 'Super Admin',   true,  'Developer access. Hidden from everyone else.'],
        'admin'         => [90,  'Admin',         true,  'Full administration. Everything except developer options.'],
        'manager'       => [60,  'Branch Manager', false, 'Runs a branch.'],
        'stock_manager' => [40,  'Stock Manager', false, 'Products, stock and purchasing.'],
        'cashier'       => [20,  'Cashier',       false, 'Works the till.'],
    ];

    public function handle(): int
    {
        $this->call('permissions:sync');

        // ── 1. Roles + ranks ──────────────────────────────────────────────
        foreach (self::RANKS as $name => [$level, $label, $isSystem, $desc]) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->update([
                'level'       => $level,
                'label'       => $label,
                'is_system'   => $isSystem,
                'description' => $desc,
            ]);
            $this->line("  role {$name} → level {$level}");
        }

        $all       = Permission::pluck('name')->all();
        $developer = $this->developerPermissions();

        // ── 2. super_admin = everything (Gate::before also bypasses) ──────
        Role::where('name', 'super_admin')->first()->syncPermissions($all);

        // ── 3. admin = everything EXCEPT developer.* ──────────────────────
        $adminPerms = array_values(array_diff($all, $developer));
        Role::where('name', 'admin')->first()->syncPermissions($adminPerms);
        $this->line('  admin → ' . count($adminPerms) . ' permissions (all but developer.*)');

        // ── 4. Reconcile the lower roles (live DB had granted them nothing) ──
        $this->grantIfEmpty('manager', [
            'dashboard.view', 'pos.access', 'pos.counter',
            'sales.view', 'sales.create', 'sales.edit', 'sales.print',
            'sale_returns.view', 'sale_returns.create',
            'quotations.view', 'quotations.create', 'quotations.convert',
            'purchases.view', 'purchases.create', 'purchases.edit',
            'purchase_returns.view', 'purchase_returns.create',
            'payments.in.view', 'payments.in.create', 'payments.out.view', 'payments.out.create',
            'products.view', 'products.create', 'products.edit', 'products.export',
            'catalog.brands.manage', 'catalog.categories.manage',
            'barcodes.print',
            'stock.view', 'stock.adjust', 'stock.transfer', 'stock.convert',
            'customers.view', 'customers.create', 'customers.edit',
            'suppliers.view', 'suppliers.create', 'suppliers.edit',
            'accounts.view', 'expenses.view', 'expenses.create',
            'counter_sessions.view',
            'reports.view', 'reports.profit', 'reports.export',
            'hrm.view', 'hrm.attendance.manage', 'hrm.leaves.request', 'hrm.leaves.approve',
            'online_orders.view', 'online_orders.manage',
            'users.view',
        ]);

        $this->grantIfEmpty('cashier', [
            'dashboard.view', 'pos.access', 'pos.counter',
            'sales.view', 'sales.create', 'sales.print',
            'customers.view', 'customers.create',
            'products.view',
        ]);

        $this->grantIfEmpty('stock_manager', [
            'dashboard.view',
            'products.view', 'products.create', 'products.edit', 'products.import', 'products.export',
            'catalog.brands.manage', 'catalog.categories.manage', 'catalog.variations.manage',
            'barcodes.print',
            'stock.view', 'stock.adjust', 'stock.transfer', 'stock.convert',
            'purchases.view', 'purchases.create',
            'purchase_returns.view', 'purchase_returns.create',
            'suppliers.view',
            'reports.view',
        ]);

        // ── 5. Only admin + super_admin see every branch ──────────────────
        foreach (['manager', 'stock_manager', 'cashier'] as $name) {
            Role::where('name', $name)->first()?->revokePermissionTo('branches.view_all');
        }

        // ── 5b. Everyone gets self-service ────────────────────────────────
        // Applied explicitly rather than via grantIfEmpty(), which only fires for
        // roles that have no permissions at all — on an existing install that
        // would silently skip every role. These only expose the holder's OWN
        // record, so they're safe for every rank.
        foreach (['manager', 'stock_manager', 'cashier'] as $name) {
            if ($role = Role::where('name', $name)->first()) {
                foreach (['hrm.self.view', 'hrm.self.leave', 'hrm.self.attendance'] as $permission) {
                    if (! $role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }

        // ── 5c. Whoever already adjusts stock can break bulk ──────────────
        // Explicit for the same reason as above: the lists in step 4 only apply
        // to a role that holds nothing yet, so an existing install would never
        // pick this up and the new screen would 403 for everyone but admin.
        foreach (['manager', 'stock_manager'] as $name) {
            $role = Role::where('name', $name)->first();
            if ($role && $role->hasPermissionTo('stock.adjust') && ! $role->hasPermissionTo('stock.convert')) {
                $role->givePermissionTo('stock.convert');
            }
        }

        // ── 6. Accounts ───────────────────────────────────────────────────
        // The old admin@freshmart.lk was super_admin; super_admin is now
        // developer-only, so move it down to the new admin role.
        if ($admin = User::where('email', 'admin@freshmart.lk')->first()) {
            $admin->syncRoles(['admin']);
            $this->line('  admin@freshmart.lk → admin role');
        }

        // Hidden developer account.
        $devEmail = $this->option('dev-email');
        $devPass  = $this->option('dev-password') ?: 'admin123';
        $dev = User::firstOrCreate(
            ['email' => $devEmail],
            [
                'name'      => 'Developer',
                'password'  => Hash::make($devPass),
                'branch_id' => User::where('email', 'admin@freshmart.lk')->value('branch_id') ?? 1,
                'status'    => 'active',
            ]
        );
        $dev->syncRoles(['super_admin']);
        $this->line("  {$devEmail} → super_admin (developer)");

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->newLine();
        $this->info('Role hierarchy ready.');
        if ($dev->wasRecentlyCreated) {
            $this->warn("Developer login: {$devEmail} / {$devPass}  ← change this password.");
        }

        return self::SUCCESS;
    }

    /** Permission names in groups flagged `developer => true`. */
    private function developerPermissions(): array
    {
        $names = [];
        foreach (config('permissions', []) as $group) {
            if (! empty($group['developer'])) {
                $names = array_merge($names, array_keys($group['permissions'] ?? []));
            }
        }
        return $names;
    }

    /**
     * Grant a baseline only when the role currently has none — so an admin's
     * hand-tuned permissions are never overwritten by re-running this.
     */
    private function grantIfEmpty(string $roleName, array $permissions): void
    {
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            return;
        }
        if ($role->permissions()->count() > 0) {
            $this->line("  {$roleName} → already has permissions, left alone");
            return;
        }
        $role->syncPermissions(array_values(array_intersect($permissions, Permission::pluck('name')->all())));
        $this->line("  {$roleName} → " . $role->permissions()->count() . ' permissions (baseline)');
    }
}
