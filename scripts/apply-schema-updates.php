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

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$dry = in_array('--dry', $argv, true);

// table => [column => the DDL fragment used to add it]
$columns = [
    // 2026-07-20 — end-of-day cash: keep a float in the till, bank the rest.
    'counters' => [
        'float_amount' => 'DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER cash_balance',
    ],
    'counter_sessions' => [
        'float_retained'     => 'DECIMAL(15,2) NULL AFTER variance',
        'deposit_amount'     => 'DECIMAL(15,2) NULL AFTER float_retained',
        'deposit_account_id' => 'BIGINT UNSIGNED NULL AFTER deposit_amount',
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
