<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    protected $fillable = ['sale_return_id', 'product_id', 'sale_item_id', 'quantity', 'unit_price', 'cost', 'subtotal'];

    public $timestamps = false;   // sale_return_items has no created_at/updated_at columns

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}