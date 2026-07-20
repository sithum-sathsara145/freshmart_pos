<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    protected $fillable = ['branch_id', 'name', 'cash_balance', 'float_amount', 'status'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}