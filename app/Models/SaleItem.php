<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'name', 'product_variation_id', 'quantity', 'unit_price', 'cost', 'discount_percent', 'tax_percent', 'subtotal'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Return lines issued against this sale line (for qty-remaining / already-returned math). */
    public function returnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}