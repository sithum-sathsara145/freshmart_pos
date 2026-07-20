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
 *  - guard():    refuse to issue more than is on hand, safely under concurrency
 */
class Inventory
{
    /**
     * Check that every product has enough on hand, holding a lock on each row
     * until the caller's transaction ends.
     *
     * Must be called INSIDE a transaction. Checking before opening one lets two
     * tills selling the last unit both pass and then both consume it, which is
     * how stock went negative; the lock makes concurrent sales of the same
     * product queue up instead.
     *
     * @param  array<int, float>  $needByProduct  product id => total quantity wanted
     * @return string|null  an explanation if short, null when everything fits
     */
    public static function guard(array $needByProduct, int $branchId): ?string
    {
        // Lowest id first: two sales covering the same products take their locks
        // in the same order and so cannot deadlock against each other.
        ksort($needByProduct);

        foreach ($needByProduct as $productId => $needed) {
            $onHand = (float) (Stock::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->value('quantity') ?? 0);

            if ($needed - $onHand > 0.0001) {
                $name = Product::where('id', $productId)->value('name') ?? "product #{$productId}";

                return "Not enough stock for \"{$name}\" — available {$onHand}, requested {$needed}.";
            }
        }

        return null;
    }
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

        // Pass 1 takes the chosen sale price's layers (FIFO); pass 2 takes any remaining
        // layers regardless of price — covers a manually overridden price at the till and
        // keeps the layers in step with the aggregate.
        $passes = (! $weighed && $salePrice !== null) ? [$salePrice, null] : [null];

        foreach ($passes as $priceFilter) {
            if ($remaining <= 0) {
                break;
            }
            $query = StockLayer::where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->where('qty_remaining', '>', 0);
            if ($priceFilter !== null) {
                $query->where('sale_price', $priceFilter);
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
