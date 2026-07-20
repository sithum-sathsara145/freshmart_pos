<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    protected $fillable = [
        'branch_id', 'name', 'cash_balance', 'float_amount',
        'retain_coins', 'retain_notes', 'cashier_book_id', 'status',
    ];

    protected $casts = [
        'cash_balance' => 'decimal:2',
        'float_amount' => 'decimal:2',
        'retain_coins' => 'boolean',
        'retain_notes' => 'array',   // denomination => how many notes stay
    ];

    /** The cash book this counter's takings are handed into. */
    public function cashierBook(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cashier_book_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}