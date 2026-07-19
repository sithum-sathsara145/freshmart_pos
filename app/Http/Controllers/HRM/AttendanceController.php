<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Staff;
use App\Support\AttendanceRecorder;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $branchId = CurrentBranch::id();
        $date     = $request->date ?? today()->toDateString();

        $attendance = Attendance::with('staff')
            ->whereDate('date', $date)
            ->whereHas('staff', fn ($q) => $q->whereBranch($branchId))
            ->get();

        $staff = Staff::whereBranch($branchId)->where('status', 'active')->orderBy('name')->get();

        $stats = [
            'present' => $attendance->where('status', 'present')->count(),
            'absent'  => $attendance->where('status', 'absent')->count(),
            'leave'   => $attendance->where('status', 'leave')->count(),
        ];

        // Keyed by staff so the sheet can show every active person with whatever
        // has been recorded for them so far.
        $rows = $attendance->keyBy('staff_id');

        return view('hrm.attendance.index', compact('attendance', 'rows', 'staff', 'date', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'staff_id' => ['required', $this->branchStaffRule()],
            'date'     => 'required|date',
            'status'   => 'required|in:present,absent,leave,half_day',
            'time_in'  => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i|after:time_in',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['staff_id' => $request->staff_id, 'date' => $request->date],
            [
                'time_in'  => $request->time_in,
                'time_out' => $request->time_out,
                'status'   => $request->status,
            ]
        );

        // Hours are always derived, never taken from the request.
        AttendanceRecorder::recompute($attendance);

        return back()->with('success', 'Attendance recorded.');
    }

    /**
     * Save the whole daily sheet in one submit. Rows left blank are skipped, so a
     * partially-filled sheet doesn't wipe anyone.
     */
    public function bulk(Request $request)
    {
        $request->validate([
            'date'              => 'required|date',
            'rows'              => 'required|array',
            'rows.*.status'     => 'nullable|in:present,absent,leave,half_day',
            'rows.*.time_in'    => 'nullable|date_format:H:i',
            'rows.*.time_out'   => 'nullable|date_format:H:i',
        ]);

        // Only staff from the current branch may be written, whatever the form posts.
        $allowed = Staff::whereBranch(CurrentBranch::id())->pluck('id')->flip();
        $saved   = 0;

        foreach ($request->input('rows', []) as $staffId => $row) {
            if (! $allowed->has((int) $staffId) || blank($row['status'] ?? null)) {
                continue;
            }

            $attendance = Attendance::updateOrCreate(
                ['staff_id' => (int) $staffId, 'date' => $request->date],
                [
                    'time_in'  => $row['time_in'] ?: null,
                    'time_out' => $row['time_out'] ?: null,
                    'status'   => $row['status'],
                ]
            );

            AttendanceRecorder::recompute($attendance);
            $saved++;
        }

        return back()->with('success', "Attendance saved for {$saved} staff member(s).");
    }

    /** Self check-in (POS/dashboard button). Always JSON. */
    public function checkIn(Request $request)
    {
        $staff = auth()->user()->staff;

        if (! $staff) {
            return response()->json([
                'success' => false,
                'message' => 'Your login is not linked to a staff record yet.',
            ], 422);
        }

        $attendance = AttendanceRecorder::clockIn($staff);

        return response()->json([
            'success' => true,
            'time'    => substr((string) $attendance->time_in, 0, 5),
        ]);
    }

    /** Self check-out. */
    public function checkOut(Request $request)
    {
        $staff = auth()->user()->staff;

        if (! $staff) {
            return response()->json([
                'success' => false,
                'message' => 'Your login is not linked to a staff record yet.',
            ], 422);
        }

        $attendance = AttendanceRecorder::clockOut($staff);

        if (! $attendance) {
            return response()->json([
                'success' => false,
                'message' => 'No check-in recorded for today.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'time'    => substr((string) $attendance->time_out, 0, 5),
            'hours'   => (float) $attendance->worked_hours,
        ]);
    }

    public function edit(Attendance $attendance)
    {
        $attendance->load('staff');
        CurrentBranch::guard($attendance->staff?->branch_id);

        return view('hrm.attendance.edit', compact('attendance'));
    }

    public function update(Request $request, Attendance $attendance)
    {
        $attendance->load('staff');
        CurrentBranch::guard($attendance->staff?->branch_id);

        $request->validate([
            'status'   => 'required|in:present,absent,leave,half_day',
            'time_in'  => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i|after:time_in',
        ]);

        $attendance->update($request->only(['time_in', 'time_out', 'status']));

        // Editing the times used to leave worked_hours/overtime untouched, which then
        // fed stale numbers straight into payroll.
        AttendanceRecorder::recompute($attendance);

        return redirect()->route('hrm.attendance.index', ['date' => $attendance->date?->toDateString()])
                         ->with('success', 'Attendance updated.');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->load('staff');
        CurrentBranch::guard($attendance->staff?->branch_id);

        $attendance->delete();

        return back()->with('success', 'Attendance entry removed.');
    }

    /** `exists:staff,id` alone accepts any branch's staff. */
    private function branchStaffRule(): Exists
    {
        return Rule::exists('staff', 'id')->where(
            fn ($q) => CurrentBranch::id() ? $q->where('branch_id', CurrentBranch::id()) : $q
        );
    }
}
