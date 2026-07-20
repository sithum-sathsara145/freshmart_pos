<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterSession extends Model
{
    protected $fillable = [
        'counter_id', 'branch_id', 'opened_by', 'closed_by',
        'opening_balance', 'opening_denoms',
        'cash_sales', 'expected_closing', 'closing_balance', 'closing_denoms',
        'variance', 'float_retained', 'retained_denoms', 'deposit_amount', 'deposit_account_id',
        'deposited_at', 'deposited_by', 'status', 'opened_at', 'closed_at',
    ];

    protected $casts = [
        'opening_denoms'   => 'array',
        'closing_denoms'   => 'array',
        'retained_denoms'  => 'array',
        'opening_balance'  => 'decimal:2',
        'cash_sales'       => 'decimal:2',
        'expected_closing' => 'decimal:2',
        'closing_balance'  => 'decimal:2',
        'variance'         => 'decimal:2',
        'float_retained'   => 'decimal:2',
        'deposit_amount'   => 'decimal:2',
        'opened_at'        => 'datetime',
        'closed_at'        => 'datetime',
        'deposited_at'     => 'datetime',
    ];

    /** Closed with cash set aside, but nobody has taken it in yet. */
    public function awaitingHandIn(): bool
    {
        return $this->status === 'closed'
            && (float) $this->deposit_amount > 0
            && $this->deposited_at === null;
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** Where the takings were banked when the counter was closed. */
    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deposit_account_id');
    }
}
