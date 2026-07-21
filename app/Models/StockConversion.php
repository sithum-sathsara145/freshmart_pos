<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record of bulk stock actually being broken down: three bags opened, so many
 * kilos or packets produced, and what the bulk had cost.
 *
 * Kept as its own record rather than a pair of stock adjustments, because the
 * two sides are one event — the cost that leaves the bulk is exactly the cost
 * that arrives in the retail stock, and that link is what keeps profit honest
 * once the retail item sells.
 */
class StockConversion extends Model
{
    protected $fillable = [
        'branch_id', 'from_product_id', 'to_product_id',
        'from_qty', 'expected_qty', 'to_qty', 'wastage_qty',
        'total_cost', 'unit_cost', 'note', 'created_by',
    ];

    protected $casts = [
        'from_qty'     => 'decimal:3',
        'expected_qty' => 'decimal:3',
        'to_qty'       => 'decimal:3',
        'wastage_qty'  => 'decimal:3',
        'total_cost'   => 'decimal:2',
        'unit_cost'    => 'decimal:2',
    ];

    public function from(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'from_product_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'to_product_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hadWastage(): bool
    {
        return (float) $this->wastage_qty > 0;
    }
}
