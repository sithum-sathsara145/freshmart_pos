<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'product_id', 'quantity', 'unit_price', 'batch_no', 'mrp', 'sale_price', 'subtotal'];

    public $timestamps = false;   // purchase_items has no created_at/updated_at columns

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
