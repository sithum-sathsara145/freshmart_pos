<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line on a cash book or bank account statement.
 *
 * Written only through App\Support\Ledger, which applies the balance change in
 * the same transaction — never created directly.
 */
class AccountTransaction extends Model
{
    protected $fillable = [
        'account_id', 'occurred_at', 'direction', 'amount', 'balance_after',
        'reference', 'description', 'source_type', 'source_id',
        'counterparty_account_id', 'created_by',
    ];

    protected $casts = [
        'occurred_at'   => 'datetime',
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** The other side of a transfer, when there is one. */
    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'counterparty_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCredit(): bool
    {
        return $this->direction === 'credit';
    }

    /** What the movement was, in words a shopkeeper reads. */
    public function label(): string
    {
        if (filled($this->description)) {
            return $this->description;
        }

        return match ($this->source_type) {
            'sale'          => 'Sale',
            'sale_return'   => 'Sale return',
            'purchase'      => 'Purchase payment',
            'purchase_return' => 'Purchase return',
            'expense'       => 'Expense',
            'transfer'      => 'Transfer',
            'counter_close' => 'Counter close',
            'opening'       => 'Opening balance',
            'manual'        => 'Manual entry',
            default         => 'Adjustment',
        };
    }
}
