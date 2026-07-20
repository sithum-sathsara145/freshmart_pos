<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'name', 'type', 'subtype', 'branch_id', 'account_number',
        'bank_name', 'bank_branch', 'opening_balance', 'is_cashier_book',
        'notes', 'balance', 'status',
    ];

    protected $casts = [
        'balance'         => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_cashier_book' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function isCash(): bool
    {
        return $this->type === 'cash';
    }

    /** "Sampath Bank · Current · 1234567890", or just the name for a cash book. */
    public function describe(): string
    {
        if ($this->isCash()) {
            return $this->name;
        }

        $parts = array_filter([
            $this->bank_name,
            $this->subtype ? ucfirst($this->subtype) : null,
            $this->account_number,
        ]);

        return $parts ? $this->name . ' — ' . implode(' · ', $parts) : $this->name;
    }

    /** Accounts a transfer may move money between. */
    public function scopeTransferable($query)
    {
        return $query->where('status', 'active')->orderBy('type')->orderBy('name');
    }
}
