<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLayer;

/**
 * Single entry point for every stock movement, so the `stock` aggregate and the
 * `stock_layers` (FIFO cost + price layers) never drift apart.
 *
 *  - addLayer(): receiving stock (purchase, opening stock, adjustment "add", transfer in)
 *  - consume():  issuing stock (sale, adjustment "remove", transfer out) — returns COGS
 */
class Inventory
{
    /** Add a cost/price layer and bump the aggregate on-hand. */
    public static function addLayer(
        int $productId,
        int $branchId,
        float $qty,
        float $cost,
        float $salePrice,
        ?string $batch = null,
        $date = null,
        ?int $purchaseItemId = null
    ): void {
        if ($qty <= 0) {
            return;
        }

        StockLayer::create([
            'product_id'       => $productId,
            'branch_id'        => $branchId,
            'purchase_item_id' => $purchaseItemId,
            'batch_no'         => $batch,
            'qty_remaining'    => $qty,
            'cost'             => $cost,
            'sale_price'       => $salePrice,
            'received_at'      => $date,
        ]);

        Stock::firstOrCreate(
            ['product_id' => $productId, 'branch_id' => $branchId],
            ['quantity'   => 0]
        )->increment('quantity', $qty);
    }

    /**
     * Issue qty from a product's layers and decrement the aggregate; returns the
     * cost of goods sold.
     *   - non-weighed: consume the chosen sale price's layers oldest-first (FIFO); COGS = layer cost
     *   - weighed:     consume oldest layers for qty; COGS = qty x current WAC (product cost)
     * Any qty beyond what layers cover is costed at the product's purchase price (legacy stock).
     */
    public static function consume(Product $product, int $branchId, float $qty, ?float $salePrice = null): float
    {
        if ($qty <= 0) {
            return 0.0;
        }

        $weighed   = (bool) $product->is_weighed;
        $remaining = $qty;
        $cogs      = 0.0;

        $query = StockLayer::where('product_id', $product->id)
            ->where('branch_id', $branchId)
            ->where('qty_remaining', '>', 0);
        if (! $weighed && $salePrice !== null) {
            $query->where('sale_price', $salePrice);
        }

        foreach ($query->orderBy('received_at')->orderBy('id')->get() as $layer) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, (float) $layer->qty_remaining);
            if (! $weighed) {
                $cogs += $take * (float) $layer->cost;
            }
            $layer->decrement('qty_remaining', $take);
            $remaining -= $take;
        }

        if ($weighed) {
            $cogs = $qty * (float) $product->purchase_price;
        } elseif ($remaining > 0) {
            $cogs += $remaining * (float) $product->purchase_price;
        }

        Stock::where('product_id', $product->id)
            ->where('branch_id', $branchId)
            ->decrement('quantity', $qty);

        return round($cogs, 2);
    }
}
