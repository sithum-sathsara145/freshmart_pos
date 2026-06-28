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
        'variance', 'status', 'opened_at', 'closed_at',
    ];

    protected $casts = [
        'opening_denoms'   => 'array',
        'closing_denoms'   => 'array',
        'opening_balance'  => 'decimal:2',
        'cash_sales'       => 'decimal:2',
        'expected_closing' => 'decimal:2',
        'closing_balance'  => 'decimal:2',
        'variance'         => 'decimal:2',
        'opened_at'        => 'datetime',
        'closed_at'        => 'datetime',
    ];

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
}
