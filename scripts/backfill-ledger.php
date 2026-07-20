<?php
/**
 * Rebuild the account ledger from the records that already exist.
 *
 * Before the ledger, an account's history was scattered: `payments` held sales,
 * purchases and transfers, `expenses` held its own account_id, and nothing tied
 * them together — a statement showed only payments whose account_id matched, so
 * transfers IN and every expense were invisible.
 *
 * This walks both tables in date order, writes one entry per movement, and sets
 * each account's opening balance to whatever is needed for the entries to land
 * on the balance the account currently holds. Nothing is invented: the closing
 * balance is left exactly as it was.
 *
 * Safe to run repeatedly — it clears and rebuilds, so a second run produces the
 * same result rather than doubling everything up.
 *
 * Usage:  php scripts/backfill-ledger.php [--dry]
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Account;
use Illuminate\Support\Facades\DB;

$dry = in_array('--dry', $argv, true);

$movements = [];

// ── payments ────────────────────────────────────────────────────────────────
// A transfer touches two accounts: out of account_id, into to_account_id.
foreach (DB::table('payments')->orderBy('created_at')->orderBy('id')->get() as $p) {
    $when = $p->created_at ?? now();

    if ($p->type === 'transfer') {
        if ($p->account_id) {
            $movements[] = [$p->account_id, $when, 'debit', $p->amount, $p->reference_no,
                $p->notes ?: 'Transfer out', 'transfer', $p->id, $p->to_account_id, $p->created_by];
        }
        if ($p->to_account_id) {
            $movements[] = [$p->to_account_id, $when, 'credit', $p->amount, $p->reference_no,
                $p->notes ?: 'Transfer in', 'transfer', $p->id, $p->account_id, $p->created_by];
        }
        continue;
    }

    if (! $p->account_id) {
        continue;
    }

    $isIn = $p->type === 'payment_in';
    $movements[] = [
        $p->account_id, $when, $isIn ? 'credit' : 'debit', $p->amount, $p->reference_no,
        $p->notes ?: ($isIn ? 'Payment received' : 'Payment made'),
        $p->sale_id ? 'sale' : ($p->purchase_id ? 'purchase' : 'manual'),
        $p->sale_id ?: $p->purchase_id, null, $p->created_by,
    ];
}

// ── expenses ────────────────────────────────────────────────────────────────
foreach (DB::table('expenses')->whereNotNull('account_id')->orderBy('expense_date')->orderBy('id')->get() as $e) {
    $movements[] = [
        $e->account_id, $e->expense_date ?: ($e->created_at ?? now()), 'debit', $e->amount,
        null, $e->description ?: 'Expense', 'expense', $e->id, null, $e->created_by,
    ];
}

// Oldest first, so running balances build up in the order things happened.
usort($movements, fn ($a, $b) => [$a[1], $a[0]] <=> [$b[1], $b[0]]);

// ── group by account and work out the opening each one needs ────────────────
$byAccount = [];
foreach ($movements as $m) {
    $byAccount[$m[0]][] = $m;
}

$accounts = Account::orderBy('id')->get();
$rows     = 0;

printf("%-26s %12s %8s %14s %14s\n", 'account', 'balance', 'entries', 'opening', 'check');

foreach ($accounts as $account) {
    $entries = $byAccount[$account->id] ?? [];

    $net = 0.0;
    foreach ($entries as $m) {
        $net += $m[2] === 'credit' ? (float) $m[3] : -(float) $m[3];
    }

    // Whatever the movements do not explain must have been there to begin with.
    $opening = round((float) $account->balance - $net, 2);

    if ($dry) {
        $rows += count($entries) + (abs($opening) > 0.004 ? 1 : 0);
    }

    if (! $dry) {
        DB::table('account_transactions')->where('account_id', $account->id)->delete();

        $running = 0.0;
        if (abs($opening) > 0.004) {
            $running = $opening;
            DB::table('account_transactions')->insert([
                'account_id'    => $account->id,
                'occurred_at'   => $entries ? $entries[0][1] : ($account->created_at ?? now()),
                'direction'     => $opening >= 0 ? 'credit' : 'debit',
                'amount'        => abs($opening),
                'balance_after' => $running,
                'description'   => 'Opening balance',
                'source_type'   => 'opening',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $rows++;
        }

        foreach ($entries as [$accId, $when, $dir, $amount, $ref, $desc, $srcType, $srcId, $counter, $by]) {
            $running = round($running + ($dir === 'credit' ? (float) $amount : -(float) $amount), 2);
            DB::table('account_transactions')->insert([
                'account_id'    => $accId,
                'occurred_at'   => $when,
                'direction'     => $dir,
                'amount'        => $amount,
                'balance_after' => $running,
                'reference'     => $ref,
                'description'   => mb_substr((string) $desc, 0, 255),
                'source_type'   => $srcType,
                'source_id'     => $srcId,
                'counterparty_account_id' => $counter,
                'created_by'    => $by,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $rows++;
        }

        $account->update(['opening_balance' => $opening]);
    }

    $derived = round($opening + $net, 2);
    printf(
        "%-26s %12s %8d %14s %14s\n",
        mb_substr($account->name, 0, 25),
        number_format((float) $account->balance, 2),
        count($entries),
        number_format($opening, 2),
        abs($derived - (float) $account->balance) < 0.005 ? 'ok' : 'MISMATCH'
    );
}

echo "\n" . ($dry ? 'would write' : 'wrote') . " $rows ledger entries\n";
