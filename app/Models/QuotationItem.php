<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $fillable = ['quotation_id', 'product_id', 'quantity', 'unit_price', 'subtotal'];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}