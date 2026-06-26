<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['name', 'type', 'branch_id', 'account_number', 'balance', 'status'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
