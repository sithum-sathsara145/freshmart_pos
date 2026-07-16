<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Resolves a report's date window from a preset (or custom from/to), the equal-length
 * previous period for comparison, a bucket granularity (hour/day/month), and zero-filled
 * bucket series — all in PHP so MySQL 5.7 needs no window functions or calendar tables.
 *
 * Usage in a controller:
 *   $range = ReportRange::fromRequest($request);
 *   $rows  = Sale::whereBetween(DB::raw('DATE(created_at)'), [$range->fromDate(), $range->toDate()])->get();
 *   $cur   = $range->series($range->bucketMap($rows, 'created_at', fn($s) => $s->total));
 *   // compare series (same length, aligned by index):
 *   $prev  = $range->series($range->bucketMap($prevRows, 'created_at', fn($s) => $s->total), $range->prevBucketKeys());
 */
class ReportRange
{
    public string $preset;
    public Carbon $from;      // start of day
    public Carbon $to;        // end of day (inclusive)
    public Carbon $prevFrom;
    public Carbon $prevTo;
    public bool   $compare;
    public string $bucket;    // 'hour' | 'day' | 'month'

    private const PRESETS = [
        'today', 'yesterday', 'last_7_days', 'last_30_days',
        'this_month', 'last_month', 'this_year', 'custom',
    ];

    public static function fromRequest($request): self
    {
        return new self(
            $request->get('range'),
            $request->get('from_date'),
            $request->get('to_date'),
            $request->boolean('compare'),
        );
    }

    public function __construct(?string $preset, ?string $from = null, ?string $to = null, bool $compare = false)
    {
        $preset = in_array($preset, self::PRESETS, true) ? $preset : 'last_30_days';
        $today  = Carbon::today();

        [$f, $t] = match ($preset) {
            'today'        => [$today->copy(), $today->copy()],
            'yesterday'    => [$today->copy()->subDay(), $today->copy()->subDay()],
            'last_7_days'  => [$today->copy()->subDays(6), $today->copy()],
            'last_30_days' => [$today->copy()->subDays(29), $today->copy()],
            'this_month'   => [$today->copy()->startOfMonth(), $today->copy()],
            'last_month'   => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year'    => [$today->copy()->startOfYear(), $today->copy()],
            'custom'       => [
                $from ? Carbon::parse($from) : $today->copy()->subDays(29),
                $to   ? Carbon::parse($to)   : $today->copy(),
            ],
        };

        if ($f->gt($t)) {
            [$f, $t] = [$t, $f];   // tolerate a reversed custom range
        }

        $this->preset  = $preset;
        $this->compare = $compare;
        $this->from    = $f->copy()->startOfDay();
        $this->to      = $t->copy()->endOfDay();

        // Equal-length previous period ending the day before `from`. Whole-day count
        // computed on midnight-normalised dates (Carbon 3's diffInDays returns a float).
        $days = (int) $this->from->copy()->startOfDay()->diffInDays($this->to->copy()->startOfDay()) + 1;
        $this->prevTo   = $this->from->copy()->subDay()->endOfDay();
        $this->prevFrom = $this->prevTo->copy()->startOfDay()->subDays($days - 1)->startOfDay();

        $this->bucket = $days <= 1 ? 'hour' : ($days <= 62 ? 'day' : 'month');
    }

    // --- Query bounds (DATE() comparisons) -------------------------------
    public function fromDate(): string     { return $this->from->toDateString(); }
    public function toDate(): string       { return $this->to->toDateString(); }
    public function prevFromDate(): string { return $this->prevFrom->toDateString(); }
    public function prevToDate(): string   { return $this->prevTo->toDateString(); }

    // --- Bucketing -------------------------------------------------------

    /** Ordered bucket keys for a window (defaults to the current period). */
    public function bucketKeys(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= $this->from;
        $to   ??= $this->to;
        $keys = [];

        if ($this->bucket === 'hour') {
            for ($h = 0; $h < 24; $h++) {
                $keys[] = sprintf('%02d', $h);
            }
        } elseif ($this->bucket === 'day') {
            $c = $from->copy()->startOfDay();
            while ($c->lte($to)) { $keys[] = $c->toDateString(); $c->addDay(); }
        } else {
            $c   = $from->copy()->startOfMonth();
            $end = $to->copy()->startOfMonth();
            while ($c->lte($end)) { $keys[] = $c->format('Y-m'); $c->addMonthNoOverflow(); }
        }

        return $keys;
    }

    /** Previous-period keys (same count as current), for index-aligned compare series. */
    public function prevBucketKeys(): array
    {
        return $this->bucketKeys($this->prevFrom, $this->prevTo);
    }

    /** Human-readable labels aligned to the current bucket keys (for the chart x-axis). */
    public function bucketLabels(): array
    {
        return array_map(function ($k) {
            return match ($this->bucket) {
                'hour'  => $k . ':00',
                'month' => Carbon::createFromFormat('Y-m', $k)->format('M Y'),
                default => Carbon::parse($k)->format('d M'),
            };
        }, $this->bucketKeys());
    }

    /** Which bucket key does a given date/datetime fall in? */
    public function keyFor($dateTime): string
    {
        $c = $dateTime instanceof Carbon ? $dateTime : Carbon::parse($dateTime);
        return match ($this->bucket) {
            'hour'  => $c->format('H'),
            'month' => $c->format('Y-m'),
            default => $c->toDateString(),
        };
    }

    /**
     * Sum a row collection into a keyed bucket map.
     * $value is a column name or a callable(row) => number.
     */
    public function bucketMap($rows, string $dateField, $value): array
    {
        $map = [];
        foreach ($rows as $row) {
            $date = is_array($row) ? ($row[$dateField] ?? null) : ($row->$dateField ?? null);
            if ($date === null) {
                continue;
            }
            $v = is_callable($value)
                ? $value($row)
                : (is_array($row) ? ($row[$value] ?? 0) : ($row->$value ?? 0));
            $k = $this->keyFor($date);
            $map[$k] = ($map[$k] ?? 0) + (float) $v;
        }
        return $map;
    }

    /** Ordered, zero-filled numeric series for a keyed map (defaults to current keys). */
    public function series(array $values, ?array $keys = null): array
    {
        $keys ??= $this->bucketKeys();
        return array_map(fn($k) => round((float) ($values[$k] ?? 0), 2), $keys);
    }

    // --- Presentation ----------------------------------------------------

    public function presets(): array
    {
        return [
            'today'        => 'Today',
            'yesterday'    => 'Yesterday',
            'last_7_days'  => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            'this_month'   => 'This month',
            'last_month'   => 'Last month',
            'this_year'    => 'This year',
            'custom'       => 'Custom range',
        ];
    }

    public function label(): string
    {
        return $this->presets()[$this->preset] ?? 'Last 30 days';
    }

    /** Query-string bag so the range persists across report pages + export links. */
    public function query(array $extra = []): array
    {
        return array_merge([
            'range'     => $this->preset,
            'from_date' => $this->fromDate(),
            'to_date'   => $this->toDate(),
            'compare'   => $this->compare ? 1 : 0,
        ], $extra);
    }

    /** Percentage change current-vs-previous, null when there's no baseline. */
    public static function delta(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.00001) {
            return null;
        }
        return round(($current - $previous) / abs($previous) * 100, 1);
    }
}
