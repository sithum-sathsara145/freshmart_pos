<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    protected $fillable = ['product_id', 'variation_value_id', 'barcode', 'purchase_price', 'sale_price'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function variationValue(): BelongsTo
    {
        return $this->belongsTo(VariationValue::class);
    }
}
