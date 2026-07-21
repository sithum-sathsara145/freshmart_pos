<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A rule for breaking one product down into another: one 20kg bag of sugar
 * makes 20kg of loose sugar, or 40 × 500g packets.
 *
 * The rule is deliberately not branch-scoped — a 20kg bag holds 20kg wherever
 * it is. Only the breaking itself belongs to a branch, because that's where
 * the stock moves. See StockConversion.
 */
class ProductConversion extends Model
{
    protected $fillable = ['from_product_id', 'to_product_id', 'yield_qty', 'status', 'created_by'];

    protected $casts = [
        'yield_qty' => 'decimal:3',
    ];

    public function from(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'from_product_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'to_product_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Weight is weight: a 20kg bag emptied into a loose-sugar bin is 20kg, so
     * the yield is fixed. Repacking into counted packets is where spillage
     * shows up, so those let the person doing it say what they really got.
     */
    public function yieldIsFixed(): bool
    {
        return (bool) $this->to?->is_weighed;
    }

    /** "1 bag → 20 kg" — how the rule reads on screen. */
    public function label(): string
    {
        $trim = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');

        return '1 ' . ($this->from?->unit ?: 'unit') . ' → '
             . $trim($this->yield_qty) . ' ' . ($this->to?->unit ?: 'unit');
    }
}
