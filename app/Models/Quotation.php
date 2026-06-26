<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = ['quote_no', 'customer_id', 'branch_id', 'user_id', 'subtotal', 'discount_amount', 'tax_amount', 'total', 'valid_till', 'notes', 'status'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }
}
