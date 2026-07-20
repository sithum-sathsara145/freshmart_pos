<?php

namespace App\Support;

use App\Models\Account;

/**
 * Picks the account a payment actually lands in.
 *
 * The till used to post every tender to the branch cash account, so card takings
 * inflated the cash balance and never reached the bank. Cash belongs in the cash
 * account; card, bank transfer and cheque settle into the bank.
 *
 * A branch with no bank account keeps the old behaviour — everything goes to the
 * one account it has — rather than dropping the payment on the floor.
 */
class TenderAccount
{
    public static function for(?int $branchId, string $method): ?Account
    {
        if (! $branchId) {
            return null;
        }

        $preferred = $method === 'cash' ? 'cash' : 'bank';

        return self::pick($branchId, $preferred, true)
            ?? self::pick($branchId, $preferred, false)
            ?? self::pick($branchId, null, true)
            ?? self::pick($branchId, null, false);
    }

    private static function pick(int $branchId, ?string $type, bool $activeOnly): ?Account
    {
        return Account::whereBranch($branchId)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($activeOnly, fn ($q) => $q->where('status', 'active'))
            ->orderBy('id')
            ->first();
    }
}
