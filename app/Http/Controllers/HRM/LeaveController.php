<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Staff;
use App\Support\CurrentBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $leaves = LeaveRequest::with('staff')
            ->whereHas('staff', fn ($q) => $q->whereBranch(CurrentBranch::id()))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('hrm.leaves.index', compact('leaves'));
    }

    public function create()
    {
        $staff = Staff::whereBranch(CurrentBranch::id())->where('status', 'active')->orderBy('name')->get();

        return view('hrm.leaves.create', compact('staff'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'staff_id'  => ['required', $this->branchStaffRule()],
            'type'      => 'required|in:annual,sick,casual,other',
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
            'reason'    => 'nullable|string',
        ]);

        $days = $this->countLeaveDays($request->from_date, $request->to_date);

        if ($days < 1) {
            return back()->withInput()->with('error', 'That range is entirely holidays — no leave days to record.');
        }

        LeaveRequest::create([
            ...$request->only(['staff_id', 'type', 'from_date', 'to_date', 'reason']),
            'days'   => $days,
            'status' => 'pending',
        ]);

        return redirect()->route('hrm.leaves.index')->with('success', 'Leave request submitted.');
    }

    public function approve(int $id)
    {
        $leave = LeaveRequest::with('staff')->findOrFail($id);
        CurrentBranch::guard($leave->staff?->branch_id);

        $leave->update(['status' => 'approved', 'approved_by' => auth()->id()]);

        // Reflect the approved range on the attendance sheet so payroll and the
        // daily view agree without anyone re-keying it.
        $current = Carbon::parse($leave->from_date);
        $end     = Carbon::parse($leave->to_date);

        while ($current->lessThanOrEqualTo($end)) {
            Attendance::updateOrCreate(
                ['staff_id' => $leave->staff_id, 'date' => $current->toDateString()],
                ['status' => 'leave']
            );
            $current->addDay();
        }

        return back()->with('success', 'Leave approved.');
    }

    public function reject(int $id)
    {
        $leave = LeaveRequest::with('staff')->findOrFail($id);
        CurrentBranch::guard($leave->staff?->branch_id);

        $leave->update(['status' => 'rejected']);

        return back()->with('success', 'Leave rejected.');
    }

    public function destroy(LeaveRequest $leave)
    {
        $leave->load('staff');
        CurrentBranch::guard($leave->staff?->branch_id);

        $leave->delete();

        return back()->with('success', 'Leave request deleted.');
    }

    // ── helpers ───────────────────────────────────────────────────────────

    /**
     * Days in the range that actually consume leave. Public/company holidays
     * don't — nobody should spend annual leave on a day the shop is closed.
     */
    private function countLeaveDays(string $from, string $to): int
    {
        $current  = Carbon::parse($from);
        $end      = Carbon::parse($to);
        $skipDays = (array) config('hrm.leave.exclude_weekdays', []);

        $holidays = config('hrm.leave.exclude_holidays', true)
            ? \App\Models\Holiday::whereBetween('date', [$current->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : (string) $d)
                ->flip()
            : collect();

        $days = 0;
        while ($current->lessThanOrEqualTo($end)) {
            $isHoliday = $holidays->has($current->toDateString());
            $isSkipped = in_array($current->format('l'), $skipDays, true);

            if (! $isHoliday && ! $isSkipped) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    private function branchStaffRule(): Exists
    {
        return Rule::exists('staff', 'id')->where(
            fn ($q) => CurrentBranch::id() ? $q->where('branch_id', CurrentBranch::id()) : $q
        );
    }
}
