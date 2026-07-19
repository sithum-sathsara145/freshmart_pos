<?php

namespace App\Support;

use App\Models\Holiday;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Leave entitlement vs what's been taken.
 *
 * Only the entitlement is stored. "Used" is always summed from approved leave
 * requests, so there is no running counter that can drift out of step with the
 * requests themselves — delete or reject a request and the balance corrects
 * itself for free.
 */
class LeaveBalance
{
    /**
     * Balances for one staff member in a year, keyed by leave type.
     *
     * @return Collection<string, array{type:string,label:string,entitled:float,used:float,remaining:float,tracked:bool}>
     */
    public static function for(Staff $staff, ?int $year = null): Collection
    {
        $year = $year ?: (int) now()->year;

        $entitlements = static::ensureFor($staff, $year)->keyBy('type');

        // Approved leave only — pending requests don't consume a balance until
        // someone actually approves them.
        $used = LeaveRequest::where('staff_id', $staff->id)
            ->where('status', 'approved')
            ->whereYear('from_date', $year)
            ->selectRaw('type, SUM(days) as days')
            ->groupBy('type')
            ->pluck('days', 'type');

        $tracked = (array) config('hrm.leave.balanced_types', []);

        return collect(config('hrm.leave.defaults', []))->map(function ($default, $type) use ($entitlements, $used, $tracked) {
            $entitled = (float) ($entitlements[$type]->entitled_days ?? $default);
            $taken    = (float) ($used[$type] ?? 0);

            return [
                'type'      => $type,
                'label'     => ucfirst($type),
                'entitled'  => $entitled,
                'used'      => $taken,
                'remaining' => round($entitled - $taken, 1),
                'tracked'   => in_array($type, $tracked, true),
            ];
        });
    }

    /**
     * Rows for a staff member's year, created from the configured defaults the
     * first time they're needed so nobody has to seed them by hand.
     */
    public static function ensureFor(Staff $staff, int $year): Collection
    {
        $existing = LeaveEntitlement::where('staff_id', $staff->id)->where('year', $year)->get();

        $missing = collect(config('hrm.leave.defaults', []))
            ->keys()
            ->diff($existing->pluck('type'));

        if ($missing->isEmpty()) {
            return $existing;
        }

        foreach ($missing as $type) {
            LeaveEntitlement::create([
                'staff_id'      => $staff->id,
                'year'          => $year,
                'type'          => $type,
                'entitled_days' => (float) config("hrm.leave.defaults.{$type}", 0),
            ]);
        }

        return LeaveEntitlement::where('staff_id', $staff->id)->where('year', $year)->get();
    }

    /**
     * Days in a range that actually consume leave. Holidays don't — nobody should
     * spend annual leave on a day the shop is shut.
     */
    public static function countDays(string $from, string $to): int
    {
        $current  = Carbon::parse($from)->startOfDay();
        $end      = Carbon::parse($to)->startOfDay();
        $skipDays = (array) config('hrm.leave.exclude_weekdays', []);

        $holidays = config('hrm.leave.exclude_holidays', true)
            ? Holiday::whereBetween('date', [$current->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : (string) $d)
                ->flip()
            : collect();

        $days = 0;
        while ($current->lessThanOrEqualTo($end)) {
            if (! $holidays->has($current->toDateString()) && ! in_array($current->format('l'), $skipDays, true)) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Why this request can't be granted, or null if it can.
     * 'other' is unpaid leave, so it is never capped.
     */
    public static function refusalReason(Staff $staff, string $type, float $days, int $year): ?string
    {
        if (! in_array($type, (array) config('hrm.leave.balanced_types', []), true)) {
            return null;
        }

        $balance = static::for($staff, $year)->get($type);

        if (! $balance || $days <= $balance['remaining']) {
            return null;
        }

        return sprintf(
            '%s has %s %s day(s) left for %d — this request is for %s.',
            $staff->name,
            rtrim(rtrim(number_format($balance['remaining'], 1), '0'), '.'),
            $type,
            $year,
            rtrim(rtrim(number_format($days, 1), '0'), '.')
        );
    }
}
