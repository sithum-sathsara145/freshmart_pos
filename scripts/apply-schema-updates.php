<?php
/**
 * Bring an existing database up to date with database_schema.sql.
 *
 * The schema here is kept as SQL dumps rather than migrations, and those dumps
 * start by dropping the database — so they can only ever be used to build a
 * fresh install. This script is the safe path for a database that already holds
 * live data: it adds any missing columns in place and touches nothing else.
 *
 * Safe to run repeatedly; columns that already exist are reported and skipped.
 *
 * Usage:  php scripts/apply-schema-updates.php [--dry]
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$dry = in_array('--dry', $argv, true);

/* ------------------------------------------------------------------ tables -- */

// 2026-07-20 — atomic document numbering.
if (! Schema::hasTable('document_sequences')) {
    if ($dry) {
        echo "  document_sequences  TABLE                WOULD CREATE\n";
    } else {
        DB::statement('
            CREATE TABLE document_sequences (
                key_name VARCHAR(50) NOT NULL PRIMARY KEY,
                next_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )
        ');
        echo "  document_sequences  TABLE                CREATED\n";
    }
}

// Seed each counter at the highest number already used, so numbering carries on
// from the existing records instead of restarting and colliding with them.
if (! $dry && Schema::hasTable('document_sequences')) {
    foreach (array_keys(DocumentNumber::DOCUMENTS) as $key) {
        $before = DB::table('document_sequences')->where('key_name', $key)->value('next_number');
        DocumentNumber::seed($key);
        $after = DB::table('document_sequences')->where('key_name', $key)->value('next_number');

        printf(
            "  %-18s %-20s %s\n",
            'document_sequences',
            $key,
            $before === null ? "seeded at {$after}" : "already at {$after}"
        );
    }
}

// 2026-07-20 — the cash book / bank account ledger.
if (! Schema::hasTable('account_transactions')) {
    if ($dry) {
        echo "  account_transactions TABLE               WOULD CREATE\n";
    } else {
        DB::statement('
            CREATE TABLE account_transactions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                account_id BIGINT UNSIGNED NOT NULL,
                occurred_at TIMESTAMP NOT NULL,
                direction ENUM("credit","debit") NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                balance_after DECIMAL(15,2) NOT NULL,
                reference VARCHAR(60) NULL,
                description VARCHAR(255) NULL,
                source_type VARCHAR(40) NULL,
                source_id BIGINT UNSIGNED NULL,
                counterparty_account_id BIGINT UNSIGNED NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_account_time (account_id, occurred_at, id),
                INDEX idx_source (source_type, source_id),
                FOREIGN KEY (account_id) REFERENCES accounts(id)
            )
        ');
        echo "  account_transactions TABLE               CREATED\n";
    }
}

/* ----------------------------------------------------------------- columns -- */

// table => [column => the DDL fragment used to add it]
$columns = [
    // 2026-07-20 — end-of-day cash: keep a float in the till, bank the rest.
    // float_amount is the minimum that must stay with the cashier; retain_coins
    // and retain_notes say which physical money makes that float up.
    'counters' => [
        'float_amount'    => 'DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER cash_balance',
        'retain_coins'    => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER float_amount',
        'retain_notes'    => 'TEXT NULL AFTER retain_coins',
        'cashier_book_id' => 'BIGINT UNSIGNED NULL AFTER retain_notes',
    ],
    'counter_sessions' => [
        'float_retained'     => 'DECIMAL(15,2) NULL AFTER variance',
        'deposit_amount'     => 'DECIMAL(15,2) NULL AFTER float_retained',
        'deposit_account_id' => 'BIGINT UNSIGNED NULL AFTER deposit_amount',
        'retained_denoms'    => 'TEXT NULL AFTER deposit_account_id',
        'deposited_at'       => 'TIMESTAMP NULL AFTER retained_denoms',
        'deposited_by'       => 'BIGINT UNSIGNED NULL AFTER deposited_at',
    ],

    // 2026-07-20 — proper cash books and bank accounts.
    'accounts' => [
        'bank_name'       => 'VARCHAR(150) NULL AFTER account_number',
        'bank_branch'     => 'VARCHAR(150) NULL AFTER bank_name',
        'subtype'         => "ENUM('savings','current') NULL AFTER bank_branch",
        'opening_balance' => 'DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER subtype',
        'is_cashier_book' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER opening_balance',
        'notes'           => 'VARCHAR(255) NULL AFTER is_cashier_book',
    ],
];

$added = 0;
$present = 0;

foreach ($columns as $table => $defs) {
    if (! Schema::hasTable($table)) {
        printf("  %-18s %-20s TABLE MISSING — skipped\n", $table, '');
        continue;
    }

    foreach ($defs as $column => $ddl) {
        if (Schema::hasColumn($table, $column)) {
            printf("  %-18s %-20s already present\n", $table, $column);
            $present++;
            continue;
        }

        if ($dry) {
            printf("  %-18s %-20s WOULD ADD\n", $table, $column);
            $added++;
            continue;
        }

        DB::statement("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$ddl}");
        printf("  %-18s %-20s ADDED\n", $table, $column);
        $added++;
    }
}

echo "\n" . ($dry ? 'would add' : 'added') . ": $added   already present: $present\n";
