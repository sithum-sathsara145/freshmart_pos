<?php

/*
|--------------------------------------------------------------------------
| Permission catalogue — the single source of truth
|--------------------------------------------------------------------------
|
| Every permission the app knows about, grouped by module. The group key is
| used to build the checkbox grid on the Roles page; the label is what a
| non-technical admin reads.
|
| Run `php artisan permissions:sync` after editing — it creates any missing
| permissions. It never deletes, so hand-made grants always survive.
|
| NOTE: the names of the original 34 seeded permissions are kept exactly as
| they were. Renaming one would orphan its existing role grants.
|
| The `developer` group is reserved for super_admin only. It is hidden from
| the Roles UI for everyone else and is never granted to `admin`.
|
*/

return [

    'dashboard' => [
        'label'       => 'Dashboard',
        'permissions' => [
            'dashboard.view' => 'View dashboard',
        ],
    ],

    'pos' => [
        'label'       => 'Point of Sale',
        'permissions' => [
            'pos.access'  => 'Use the POS / checkout',
            'pos.counter' => 'Open &amp; close the counter (cash session)',
        ],
    ],

    'sales' => [
        'label'       => 'Sales',
        'permissions' => [
            'sales.view'   => 'View sales',
            'sales.create' => 'Create sales',
            'sales.edit'   => 'Edit sales',
            'sales.delete' => 'Delete / void sales',
            'sales.print'  => 'Print invoices &amp; receipts',
        ],
    ],

    'sale_returns' => [
        'label'       => 'Sale returns',
        'permissions' => [
            'sale_returns.view'    => 'View sale returns',
            'sale_returns.create'  => 'Create sale returns (credit notes)',
            'sale_returns.reverse' => 'Reverse a sale return',
        ],
    ],

    'quotations' => [
        'label'       => 'Quotations',
        'permissions' => [
            'quotations.view'    => 'View quotations',
            'quotations.create'  => 'Create quotations',
            'quotations.convert' => 'Convert a quotation to a sale',
            'quotations.delete'  => 'Delete quotations',
        ],
    ],

    'purchases' => [
        'label'       => 'Purchases',
        'permissions' => [
            'purchases.view'   => 'View purchases',
            'purchases.create' => 'Create purchases',
            'purchases.edit'   => 'Edit purchases',
            'purchases.delete' => 'Delete purchases',
            'purchases.import' => 'Import received goods from CSV / Excel',
        ],
    ],

    'purchase_returns' => [
        'label'       => 'Purchase returns',
        'permissions' => [
            'purchase_returns.view'    => 'View purchase returns',
            'purchase_returns.create'  => 'Create purchase returns (debit notes)',
            'purchase_returns.reverse' => 'Reverse a purchase return',
        ],
    ],

    'payments' => [
        'label'       => 'Payments',
        'permissions' => [
            'payments.in.view'    => 'View money received',
            'payments.in.create'  => 'Record money received',
            'payments.out.view'   => 'View money paid out',
            'payments.out.create' => 'Record money paid out',
        ],
    ],

    'products' => [
        'label'       => 'Products',
        'permissions' => [
            'products.view'   => 'View products',
            'products.create' => 'Create products',
            'products.edit'   => 'Edit products',
            'products.delete' => 'Delete products',
            'products.import' => 'Import products from CSV / Excel',
            'products.export' => 'Export the product list',
        ],
    ],

    'catalog' => [
        'label'       => 'Brands, categories &amp; variations',
        'permissions' => [
            'catalog.brands.manage'     => 'Manage brands',
            'catalog.categories.manage' => 'Manage categories',
            'catalog.variations.manage' => 'Manage variations',
        ],
    ],

    'barcodes' => [
        'label'       => 'Barcodes &amp; labels',
        'permissions' => [
            'barcodes.print' => 'Print barcodes &amp; labels',
        ],
    ],

    'stock' => [
        'label'       => 'Stock',
        'permissions' => [
            'stock.view'     => 'View stock levels',
            'stock.adjust'   => 'Adjust stock',
            'stock.transfer' => 'Transfer stock between branches',
            'stock.convert'  => 'Break bulk packs into retail stock',
        ],
    ],

    'customers' => [
        'label'       => 'Customers',
        'permissions' => [
            'customers.view'   => 'View customers',
            'customers.create' => 'Create customers',
            'customers.edit'   => 'Edit customers',
            'customers.delete' => 'Delete customers',
        ],
    ],

    'suppliers' => [
        'label'       => 'Suppliers',
        'permissions' => [
            'suppliers.view'   => 'View suppliers',
            'suppliers.create' => 'Create suppliers',
            'suppliers.edit'   => 'Edit suppliers',
            'suppliers.delete' => 'Delete suppliers',
            'suppliers.import' => 'Import suppliers from CSV / Excel',
            'suppliers.export' => 'Export the supplier list',
        ],
    ],

    'accounts' => [
        'label'       => 'Cash &amp; bank accounts',
        'permissions' => [
            'accounts.view'     => 'View accounts, statements &amp; balances',
            'accounts.manage'   => 'Create / edit / delete accounts',
            'accounts.transfer' => 'Transfer money between accounts',
            'accounts.entry'    => 'Record a deposit or withdrawal by hand',
            'accounts.handin'   => 'Record cash handed in from a counter',
        ],
    ],

    'expenses' => [
        'label'       => 'Expenses',
        'permissions' => [
            'expenses.view'       => 'View expenses',
            'expenses.create'     => 'Record expenses',
            'expenses.edit'       => 'Edit expenses',
            'expenses.delete'     => 'Delete expenses',
            'expenses.categories' => 'Manage expense categories',
        ],
    ],

    'counter_sessions' => [
        'label'       => 'Counter sessions',
        'permissions' => [
            'counter_sessions.view' => 'View counter / till sessions',
        ],
    ],

    'reports' => [
        'label'       => 'Reports',
        'permissions' => [
            'reports.view'   => 'View reports',
            'reports.profit' => 'View profit, cost &amp; margin figures',
            'reports.export' => 'Export reports (PDF / Excel)',
        ],
    ],

    'hrm' => [
        'label'       => 'Staff (HRM)',
        'permissions' => [
            'hrm.view'               => 'View the HRM area',
            'hrm.staff.manage'       => 'Add / edit staff members',
            'hrm.attendance.manage'  => 'Manage attendance',
            'hrm.leaves.request'     => 'File or delete leave requests',
            'hrm.leaves.approve'     => 'Approve or reject leave',
            'hrm.payroll.manage'     => 'Generate &amp; manage payroll',
            'hrm.holidays.manage'    => 'Manage holidays',
        ],
    ],

    /*
    | Self-service is deliberately its own group, NOT part of the hrm group above.
    | Those permissions govern the management area (other people's records); these
    | only ever expose the signed-in user's own data, so a cashier can hold all of
    | them without gaining any visibility into HRM.
    */
    'self_service' => [
        'label'       => 'My HR (self-service)',
        'permissions' => [
            'hrm.self.view'       => 'View own profile, attendance &amp; payslips',
            'hrm.self.leave'      => 'Request own leave',
            'hrm.self.attendance' => 'Check in / out for oneself',
        ],
    ],

    'online_orders' => [
        'label'       => 'Online orders',
        'permissions' => [
            'online_orders.view'   => 'View online orders',
            'online_orders.manage' => 'Manage online orders',
        ],
    ],

    'website' => [
        'label'       => 'Website',
        'permissions' => [
            'website.manage' => 'Manage the website &amp; banners',
        ],
    ],

    'settings' => [
        'label'       => 'Settings',
        'permissions' => [
            'settings.access'   => 'Open settings',
            'settings.api_keys' => 'View &amp; change API keys',
        ],
    ],

    'users' => [
        'label'       => 'Users',
        'permissions' => [
            'users.view'   => 'View user accounts',
            'users.manage' => 'Create, edit &amp; deactivate users (ranked below you)',
        ],
    ],

    'roles' => [
        'label'       => 'Roles &amp; permissions',
        'permissions' => [
            'roles.view'   => 'View roles',
            'roles.manage' => 'Create roles &amp; set their permissions (ranked below you)',
        ],
    ],

    'branches' => [
        'label'       => 'Branches',
        'permissions' => [
            'branches.view_all' => 'See data from all branches (not just their own)',
        ],
    ],

    // Reserved for developers. Only ever granted to super_admin, and hidden
    // from the Roles UI for everyone else.
    'developer' => [
        'label'       => 'Developer',
        'developer'   => true,
        'permissions' => [
            'developer.access' => 'Access developer-only options',
        ],
    ],

];
