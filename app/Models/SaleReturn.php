<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = ['credit_note_no', 'sale_id', 'customer_id', 'reason', 'return_amount', 'refund_method', 'created_by'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    /** Who issued the credit note — the column was always there, the relation wasn't. */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}