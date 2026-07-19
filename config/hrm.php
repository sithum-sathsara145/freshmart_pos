<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Job titles
    |--------------------------------------------------------------------------
    |
    | A staff member's job title is an HR concept and is deliberately NOT the same
    | thing as their system role (Spatie): a cleaner or security guard has a job
    | title but usually no login at all, while the developer account has a login
    | and no HR record.
    |
    | This list previously existed twice — once hardcoded in the create form and a
    | DIFFERENT list in the index filter — so the filter could never match most
    | records. Both now read from here. The list is the union of the two originals
    | so existing rows (Supervisor, Stock Manager, ...) stay valid.
    |
    */
    'job_titles' => [
        'Manager'         => 'Manager',
        'Supervisor'      => 'Supervisor',
        'Cashier'         => 'Cashier',
        'Sales Assistant' => 'Sales Assistant',
        'Stock Manager'   => 'Stock Manager',
        'Stock Keeper'    => 'Stock Keeper',
        'Delivery'        => 'Delivery',
        'Cleaner'         => 'Cleaner',
        'Security'        => 'Security',
        'Other'           => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Leave entitlements (days per year)
    |--------------------------------------------------------------------------
    |
    | Defaults follow the Sri Lankan Shop & Office Employees Act. Created per
    | staff member per year on first access, then editable per person.
    |
    */
    'leave' => [
        'defaults' => [
            'annual' => 14,
            'casual' => 7,
            'sick'   => 7,
            'other'  => 0,   // unpaid — not capped
        ],

        // Types that draw down a balance. 'other' is unpaid, so it never blocks.
        'balanced_types' => ['annual', 'casual', 'sick'],

        // Public/company holidays inside a leave range don't consume leave days.
        'exclude_holidays' => true,

        // A supermarket trades 7 days a week, so weekends are ordinary working
        // days by default. Set to a list of day names to exclude, e.g. ['Sunday'].
        'exclude_weekdays' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payroll
    |--------------------------------------------------------------------------
    |
    | EPF: 8% employee (deducted) + 12% employer. ETF: 3% employer.
    | ETF and employer EPF are employer costs and must NEVER reduce net pay —
    | deducting ETF from the employee was a real bug that underpaid everyone.
    |
    */
    'payroll' => [
        'working_days_per_month' => 26,
        'hours_per_day'          => 8,
        'overtime_multiplier'    => 1.5,

        'epf_employee_rate' => 0.08,
        'epf_employer_rate' => 0.12,
        'etf_rate'          => 0.03,

        // Approved leave and public holidays still earn a day's pay for
        // monthly-paid staff.
        'pay_approved_leave' => true,
        'pay_holidays'       => true,
    ],

];
