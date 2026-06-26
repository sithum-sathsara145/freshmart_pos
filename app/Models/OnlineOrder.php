<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class OnlineOrder extends Model
{
    protected $fillable = ['order_no', 'customer_id', 'customer_name', 'customer_phone', 'customer_address', 'delivery_type', 'subtotal', 'delivery_charge', 'total', 'status', 'notes', 'branch_id'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(OnlineOrderItem::class);
    }
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}