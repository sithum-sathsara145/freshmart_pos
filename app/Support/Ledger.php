<?php

namespace App\Support;

use App\Models\Account;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Single entry point for every movement of money in or out of an account, so a
 * cash book or bank account reads like a bank statement.
 *
 * Balances used to be nudged by increment('balance') / decrement('balance')
 * scattered across the controllers, with the history living partly in
 * `payments` and partly in `expenses` — transfers in and expenses never showed
 * on a statement at all. Every movement now writes an entry here alongside the
 * balance change, in the same transaction, so the two cannot disagree.
 *
 * The account row is locked for the update, so two movements against the same
 * account queue up rather than both reading the same starting balance.
 */
class Ledger
{
    /** Money in. */
    public static function credit(Account|int $account, float $amount, array $meta = []): ?AccountTransaction
    {
        return self::post($account, 'credit', $amount, $meta);
    }

    /** Money out. */
    public static function debit(Account|int $account, float $amount, array $meta = []): ?AccountTransaction
    {
        return self::post($account, 'debit', $amount, $meta);
    }

    /**
     * Move money between two accounts as one debit and one credit.
     *
     * @return string|null  an explanation if it could not be done
     */
    public static function transfer(Account|int $from, Account|int $to, float $amount, array $meta = []): ?string
    {
        $fromId = $from instanceof Account ? $from->id : (int) $from;
        $toId   = $to instanceof Account ? $to->id : (int) $to;

        if ($fromId === $toId) {
            return 'Pick two different accounts.';
        }
        if ($amount <= 0) {
            return 'Enter an amount greater than zero.';
        }

        return DB::transaction(function () use ($fromId, $toId, $amount, $meta) {
            // Lowest id first so two transfers involving the same pair of
            // accounts in opposite directions cannot deadlock.
            $ids = [$fromId, $toId];
            sort($ids);
            $locked = Account::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            $source      = $locked[$fromId] ?? null;
            $destination = $locked[$toId] ?? null;

            if (! $source || ! $destination) {
                return 'One of those accounts no longer exists.';
            }
            if ((float) $source->balance + 0.0001 < $amount) {
                return 'Not enough in ' . $source->name . ' — it holds Rs. ' . number_format((float) $source->balance, 2) . '.';
            }

            $reference   = $meta['reference'] ?? 'TRF-' . strtoupper(\Illuminate\Support\Str::random(8));
            $description = $meta['description'] ?? null;

            self::write($source, 'debit', $amount, [
                'reference'   => $reference,
                'description' => $description ?? "Transfer to {$destination->name}",
                'source_type' => $meta['source_type'] ?? 'transfer',
                'source_id'   => $meta['source_id'] ?? null,
                'counterparty_account_id' => $destination->id,
                'occurred_at' => $meta['occurred_at'] ?? null,
            ]);

            self::write($destination, 'credit', $amount, [
                'reference'   => $reference,
                'description' => $description ?? "Transfer from {$source->name}",
                'source_type' => $meta['source_type'] ?? 'transfer',
                'source_id'   => $meta['source_id'] ?? null,
                'counterparty_account_id' => $source->id,
                'occurred_at' => $meta['occurred_at'] ?? null,
            ]);

            return null;
        });
    }

    /** The balance an account's own entries add up to — used to check the stored one. */
    public static function derivedBalance(Account|int $account): float
    {
        $id = $account instanceof Account ? $account->id : (int) $account;

        $sums = AccountTransaction::where('account_id', $id)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0) AS total")
            ->value('total');

        return round((float) $sums, 2);
    }

    private static function post(Account|int $account, string $direction, float $amount, array $meta): ?AccountTransaction
    {
        if ($amount <= 0) {
            return null;   // nothing moved; callers pass zero amounts freely
        }

        $id = $account instanceof Account ? $account->id : (int) $account;

        return DB::transaction(function () use ($id, $direction, $amount, $meta) {
            $locked = Account::whereKey($id)->lockForUpdate()->first();
            if (! $locked) {
                return null;
            }

            return self::write($locked, $direction, $amount, $meta);
        });
    }

    /** Apply the movement to an already-locked account and record it. */
    private static function write(Account $account, string $direction, float $amount, array $meta): AccountTransaction
    {
        $amount  = round($amount, 2);
        $balance = round((float) $account->balance + ($direction === 'credit' ? $amount : -$amount), 2);

        $account->forceFill(['balance' => $balance])->save();

        return AccountTransaction::create([
            'account_id'    => $account->id,
            'occurred_at'   => $meta['occurred_at'] ?? now(),
            'direction'     => $direction,
            'amount'        => $amount,
            'balance_after' => $balance,
            'reference'     => $meta['reference'] ?? null,
            'description'   => $meta['description'] ?? null,
            'source_type'   => $meta['source_type'] ?? null,
            'source_id'     => $meta['source_id'] ?? null,
            'counterparty_account_id' => $meta['counterparty_account_id'] ?? null,
            'created_by'    => $meta['created_by'] ?? auth()->id(),
        ]);
    }
}
