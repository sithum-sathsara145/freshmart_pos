<?php

namespace App\Support;

/**
 * Works out what a cashier keeps in the drawer when the counter closes, and
 * what goes into the cash book.
 *
 * The rule the shop runs on:
 *   - coins always stay, so there is change to start the next day with
 *   - a set number of Rs 20 / 50 / 100 notes stay, as the admin decided
 *   - on top of that there is a minimum the drawer must not drop below; if the
 *     coins and set notes fall short of it, more notes stay until it is met
 *
 * Everything above that is handed in.
 */
class CashRetention
{
    /** Anything this size or smaller is a coin. */
    public const COIN_CEILING = 10;

    /**
     * @param  array<int|string, int>  $counted  denomination => how many were counted
     * @param  array{coins:bool, notes:array<int,int>, minimum:float}  $rule
     * @return array{keep:array<int,int>, move:array<int,int>, keepTotal:float, moveTotal:float, toppedUp:float}
     */
    public static function split(array $counted, array $rule): array
    {
        $counted = self::clean($counted);
        $keep    = [];

        // 1. Coins, all of them.
        if ($rule['coins'] ?? true) {
            foreach ($counted as $denom => $count) {
                if ($denom <= self::COIN_CEILING) {
                    $keep[$denom] = $count;
                }
            }
        }

        // 2. The notes the admin wants held back, as far as there are any.
        //    Tolerates a JSON string in case the column was written raw.
        $wantedNotes = $rule['notes'] ?? [];
        if (is_string($wantedNotes)) {
            $wantedNotes = json_decode($wantedNotes, true) ?: [];
        }

        foreach ($wantedNotes as $denom => $wanted) {
            $denom  = (int) $denom;
            $wanted = max(0, (int) $wanted);
            if ($denom <= self::COIN_CEILING || $wanted === 0) {
                continue;
            }
            $available = ($counted[$denom] ?? 0) - ($keep[$denom] ?? 0);
            if ($available > 0) {
                $keep[$denom] = ($keep[$denom] ?? 0) + min($wanted, $available);
            }
        }

        // 3. Top up to the minimum if what is held back does not reach it.
        $minimum  = max(0, (float) ($rule['minimum'] ?? 0));
        $toppedUp = 0.0;

        while (self::total($keep) + 0.0001 < $minimum) {
            $shortfall = $minimum - self::total($keep);
            $spare     = self::spare($counted, $keep);

            if (! $spare) {
                break;            // nothing left to keep; the drawer simply holds less
            }

            // The largest note that still fits under the shortfall, so the drawer
            // is topped up without overshooting more than it has to. When none
            // fits, the smallest note left overshoots by the least.
            $pick = null;
            foreach ($spare as $denom => $count) {
                if ($denom <= $shortfall && ($pick === null || $denom > $pick)) {
                    $pick = $denom;
                }
            }
            $pick ??= min(array_keys($spare));

            $keep[$pick] = ($keep[$pick] ?? 0) + 1;
            $toppedUp   += $pick;
        }

        $move = self::spare($counted, $keep);

        krsort($keep);
        krsort($move);

        return [
            'keep'      => $keep,
            'move'      => $move,
            'keepTotal' => self::total($keep),
            'moveTotal' => self::total($move),
            'toppedUp'  => $toppedUp,
        ];
    }

    /** The value of a denomination => count map. */
    public static function total(array $denoms): float
    {
        $total = 0.0;
        foreach ($denoms as $denom => $count) {
            $total += (int) $denom * (int) $count;
        }

        return round($total, 2);
    }

    /** Counts that are actually present, keyed by integer denomination. */
    private static function clean(array $denoms): array
    {
        $clean = [];
        foreach ($denoms as $denom => $count) {
            $denom = (int) $denom;
            $count = (int) $count;
            if ($denom > 0 && $count > 0) {
                $clean[$denom] = $count;
            }
        }
        krsort($clean);

        return $clean;
    }

    /** What is counted but not being kept. */
    private static function spare(array $counted, array $keep): array
    {
        $spare = [];
        foreach ($counted as $denom => $count) {
            $left = $count - ($keep[$denom] ?? 0);
            if ($left > 0) {
                $spare[$denom] = $left;
            }
        }

        return $spare;
    }
}
