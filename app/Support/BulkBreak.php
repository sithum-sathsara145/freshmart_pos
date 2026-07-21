<?php

namespace App\Support;

use App\Models\ProductConversion;
use App\Models\Stock;
use App\Models\StockConversion;
use Illuminate\Support\Facades\DB;

/**
 * Breaking bulk stock down into the retail item it is sold as.
 *
 * A 20kg bag of sugar and loose sugar by the kilo are two products with two
 * prices — which is the point, since it stops a retail customer being charged
 * the wholesale rate. The cost has to survive the move between them: whatever
 * the bag actually cost is taken out of its FIFO layers and put straight back
 * into the retail layers, so once that sugar sells the profit is measured
 * against what the shop really paid rather than a guess.
 */
class BulkBreak
{
    /**
     * Why this break can't happen, or null if it can.
     *
     * @param  float  $bulkQty    how many bulk units are being opened
     * @param  float  $produced   what actually came out of them
     */
    public static function refusalReason(ProductConversion $rule, int $branchId, float $bulkQty, float $produced): ?string
    {
        if (! $rule->isActive()) {
            return 'That breakdown is switched off.';
        }

        if ($bulkQty <= 0) {
            return 'Say how many to break.';
        }

        if ($produced <= 0) {
            return 'A break has to produce something.';
        }

        $onHand = (float) (Stock::where('product_id', $rule->from_product_id)
            ->where('branch_id', $branchId)->value('quantity') ?? 0);

        if ($bulkQty > $onHand + 0.0005) {
            return sprintf(
                'Only %s %s of %s in stock — you are breaking %s.',
                static::trim($onHand), $rule->from?->unit ?: 'unit', $rule->from?->name, static::trim($bulkQty)
            );
        }

        // Producing more than the bag holds means the rule or the count is wrong,
        // and letting it through would invent stock out of nothing.
        $expected = static::expectedYield($rule, $bulkQty);
        if ($produced > $expected + 0.0005) {
            return sprintf(
                'A break of %s should give at most %s %s, not %s.',
                static::trim($bulkQty), static::trim($expected), $rule->to?->unit ?: 'unit', static::trim($produced)
            );
        }

        return null;
    }

    public static function expectedYield(ProductConversion $rule, float $bulkQty): float
    {
        return round($bulkQty * (float) $rule->yield_qty, 3);
    }

    /**
     * Do the break. Consumes the bulk at its real cost and re-lays that cost
     * into the retail product, priced at the retail product's own sale price.
     *
     * Wrapped in a transaction: a half-done break would take bags off the shelf
     * without putting anything back.
     */
    public static function run(
        ProductConversion $rule,
        int $branchId,
        float $bulkQty,
        float $produced,
        ?string $note = null
    ): StockConversion {
        return DB::transaction(function () use ($rule, $branchId, $bulkQty, $produced, $note) {
            $rule->loadMissing(['from', 'to']);

            // Taking the bulk out returns what it actually cost, layer by layer.
            $cost = Inventory::consume($rule->from, $branchId, $bulkQty);

            // Spread that whole cost over what really came out, so nothing is lost
            // or invented: short yield simply makes each unit cost a little more.
            $unitCost = $produced > 0 ? round($cost / $produced, 2) : 0.0;

            // Carry the cost onto the retail product the same way a purchase does,
            // or the stock arrives costing nothing. It matters most for weighed
            // items: their COGS is read from purchase_price rather than the layer,
            // so skipping this would sell broken-down sugar at 100% reported profit.
            static::priceInto($rule->to, $branchId, $produced, $unitCost);

            Inventory::addLayer(
                $rule->to_product_id,
                $branchId,
                $produced,
                $unitCost,
                (float) $rule->to->sale_price,
                'BREAK',
                now()->toDateString()
            );

            $expected = static::expectedYield($rule, $bulkQty);

            return StockConversion::create([
                'branch_id'       => $branchId,
                'from_product_id' => $rule->from_product_id,
                'to_product_id'   => $rule->to_product_id,
                'from_qty'        => $bulkQty,
                'expected_qty'    => $expected,
                'to_qty'          => $produced,
                'wastage_qty'     => round(max(0, $expected - $produced), 3),
                'total_cost'      => round($cost, 2),
                'unit_cost'       => $unitCost,
                'note'            => $note ?: null,
                'created_by'      => auth()->id(),
            ]);
        });
    }

    /**
     * Put the cost of the broken stock onto the retail product, mirroring what a
     * purchase does — weighed items blend into a running weighted average, since
     * that is the figure their COGS is taken from; counted items just record the
     * latest cost, because each of their layers carries its own.
     *
     * Must run BEFORE the new layer is added, or the incoming quantity would be
     * counted on both sides of the average.
     */
    private static function priceInto(\App\Models\Product $product, int $branchId, float $qty, float $unitCost): void
    {
        if ($product->is_weighed) {
            $onHand = (float) (Stock::where('product_id', $product->id)
                ->where('branch_id', $branchId)->value('quantity') ?? 0);
            $newQty = $onHand + $qty;

            $product->purchase_price = $newQty > 0
                ? round((($onHand * (float) $product->purchase_price) + ($qty * $unitCost)) / $newQty, 2)
                : round($unitCost, 2);
        } else {
            $product->purchase_price = round($unitCost, 2);
        }

        $product->save();
    }

    private static function trim($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3), '0'), '.');
    }
}
