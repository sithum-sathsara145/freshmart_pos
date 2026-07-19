<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Staff;
use Carbon\Carbon;

/**
 * The single writer for attendance rows.
 *
 * Attendance arrives from three places — a manager marking the daily sheet, a
 * cashier opening their POS counter session, and staff self check-in — and all
 * three must agree on how hours are derived. Keeping the arithmetic here is what
 * stops those paths drifting apart.
 */
class AttendanceRecorder
{
    /**
     * Record an arrival. Safe to call repeatedly: the earliest time_in of the day
     * wins, and a status a manager already set (leave / half day) is never
     * overwritten by an automatic clock-in.
     */
    public static function clockIn(Staff $staff, ?Carbon $at = null): Attendance
    {
        $at = $at ?: now();

        $attendance = Attendance::firstOrNew([
            'staff_id' => $staff->id,
            'date'     => $at->toDateString(),
        ]);

        if (blank($attendance->time_in) || $at->format('H:i:s') < $attendance->time_in) {
            $attendance->time_in = $at->format('H:i:s');
        }

        // Only claim "present" when nothing more specific has been recorded.
        if (blank($attendance->status) || $attendance->status === 'absent') {
            $attendance->status = 'present';
        }

        $attendance->save();

        return static::recompute($attendance);
    }

    /**
     * Record a departure. The latest time_out of the day wins, so closing a second
     * till later in the day extends the shift rather than truncating it.
     */
    public static function clockOut(Staff $staff, ?Carbon $at = null): ?Attendance
    {
        $at = $at ?: now();

        $attendance = Attendance::where('staff_id', $staff->id)
            ->whereDate('date', $at->toDateString())
            ->first();

        if (! $attendance) {
            return null;
        }

        if (blank($attendance->time_out) || $at->format('H:i:s') > $attendance->time_out) {
            $attendance->time_out = $at->format('H:i:s');
            $attendance->save();
        }

        return static::recompute($attendance);
    }

    /**
     * Derive worked/overtime hours from the recorded times.
     *
     * Carbon 3 returns a NEGATIVE diff when the argument is in the past, so the
     * original `now()->diffInMinutes($timeIn)` produced negative hours and never
     * any overtime. Both ends are built as full datetimes and ordered explicitly.
     */
    public static function recompute(Attendance $attendance): Attendance
    {
        if (blank($attendance->time_in) || blank($attendance->time_out)) {
            $attendance->worked_hours   = 0;
            $attendance->overtime_hours = 0;
            $attendance->save();

            return $attendance;
        }

        $date = $attendance->date instanceof Carbon
            ? $attendance->date->toDateString()
            : (string) $attendance->date;

        $in  = Carbon::parse($date . ' ' . $attendance->time_in);
        $out = Carbon::parse($date . ' ' . $attendance->time_out);

        // A shift that ends "before" it started crossed midnight.
        if ($out->lessThan($in)) {
            $out->addDay();
        }

        $hours   = round($in->diffInMinutes($out) / 60, 2);
        $fullDay = (float) config('hrm.payroll.hours_per_day', 8);

        $attendance->worked_hours   = $hours;
        $attendance->overtime_hours = round(max(0, $hours - $fullDay), 2);
        $attendance->save();

        return $attendance;
    }

    /**
     * Attendance for whoever is logged in, if they have an HR record at all.
     * Returns null rather than throwing — most callers sit in flows (opening a
     * till) that must never break because HR data is incomplete.
     */
    public static function staffFor(?\App\Models\User $user): ?Staff
    {
        return $user?->staff;
    }
}
