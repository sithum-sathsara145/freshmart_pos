<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A FIFO / batch cost layer for a product at a branch. Each purchase line creates
 * one layer carrying its own cost AND sale price, so the same table drives:
 *   - FIFO costing (consume oldest qty_remaining first; COGS = layer cost)
 *   - multiple POS prices (distinct sale_price of in-stock layers = price options)
 */
class StockLayer extends Model
{
    protected $fillable = [
        'product_id', 'branch_id', 'purchase_item_id', 'batch_no',
        'qty_remaining', 'cost', 'sale_price', 'received_at',
    ];

    protected $casts = [
        'qty_remaining' => 'decimal:3',
        'cost'          => 'decimal:2',
        'sale_price'    => 'decimal:2',
        'received_at'   => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** In-stock layers for a product at a branch, oldest first (FIFO order). */
    public function scopeAvailable($query, int $productId, int $branchId)
    {
        return $query->where('product_id', $productId)
                     ->where('branch_id', $branchId)
                     ->where('qty_remaining', '>', 0)
                     ->orderBy('received_at')
                     ->orderBy('id');
    }
}
