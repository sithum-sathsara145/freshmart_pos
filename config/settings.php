<?php

/*
|--------------------------------------------------------------------------
| Settings catalogue — which keys each settings screen is allowed to write
|--------------------------------------------------------------------------
|
| The save handlers used to walk every posted field and store it, so anything
| that reached the request became a row in the settings table: a stray input,
| a renamed field leaving its old value behind, or a hand-crafted post writing
| a key the screen never owned. They now only write the keys listed here.
|
| Add a key here when you add its field to the form, or the field will submit
| and silently do nothing.
|
| `fields` are stored whenever they are present in the request. `toggles` are
| checkboxes, which a browser omits entirely when unticked — they are stored as
| 1 or 0 from whether they arrived, so a toggle can actually be turned back off.
|
| Keys NOT listed are not deleted, just not writable from that screen.
| `loyalty_earn_rate`, for instance, is seeded and read at runtime but has no
| field on any settings page.
|
| API credentials are deliberately absent: they are encrypted at rest and go
| through SettingController::saveApiKeys, never the plaintext save. See
| config/api_credentials.php.
|
*/

return [

    // Settings → Business / Receipt / Credit / Hardware / Tax / Scale / Backup
    'pos' => [
        'fields' => [
            // Business information
            'business_name',
            'address',
            'phone',
            'email',
            'currency',
            'date_format',

            // Receipt
            'receipt_template',
            'receipt_footer',

            // Credit sales
            'credit_terms',

            // Tax
            'default_tax_rate',

            // Weighing-scale barcodes (selects and numbers — always submitted)
            'internal_barcode_prefix',
            'scale_enabled',
            'scale_prefix',
            'scale_embed',
            'scale_plu_length',
            'scale_value_length',
            'scale_value_divisor',
            'scale_total_length',
        ],

        'toggles' => [
            // Receipt
            'show_logo',
            'show_customer',
            'show_tax',
            'show_loyalty',

            // Credit sales
            'allow_credit_new_customers',

            // Hardware
            'receipt_printer',
            'cash_drawer',
            'barcode_scanner',
            'customer_display',
            'touch_screen',
            'weighing_scale',

            // Tax
            'tax_enabled',
            'tax_inclusive',
            'show_tax_receipt',

            // Backup
            'auto_backup',
        ],
    ],

    // Online → Website Setup
    'website' => [
        'fields'  => [],
        'toggles' => [
            'show_stock_status',
            'show_discount_badge',
            'enable_ordering',
            'show_categories',
        ],
    ],

];
