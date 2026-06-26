<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Model;

class OnlineOrderItem extends Model
{
    protected $fillable = ['online_order_id', 'product_id', 'quantity', 'unit_price', 'subtotal'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(OnlineOrder::class, 'online_order_id');
    }
}