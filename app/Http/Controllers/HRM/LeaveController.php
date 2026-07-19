<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Staff;
use App\Support\CurrentBranch;
use App\Support\LeaveBalance;
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

    public function create(Request $request)
    {
        $staff = Staff::whereBranch(CurrentBranch::id())->where('status', 'active')->orderBy('name')->get();

        // Balances for every selectable person, so the form can show the remaining
        // days for whoever is picked without another round-trip.
        $year     = (int) now()->year;
        $balances = $staff->mapWithKeys(fn ($s) => [$s->id => LeaveBalance::for($s, $year)]);

        return view('hrm.leaves.create', compact('staff', 'balances', 'year'));
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

        $days = LeaveBalance::countDays($request->from_date, $request->to_date);

        if ($days < 1) {
            return back()->withInput()->with('error', 'That range is entirely holidays — no leave days to record.');
        }

        $staff = Staff::findOrFail($request->staff_id);
        $year  = (int) Carbon::parse($request->from_date)->year;

        // Checked server-side; the form only hides the option.
        if ($reason = LeaveBalance::refusalReason($staff, $request->type, $days, $year)) {
            return back()->withInput()->with('error', $reason);
        }

        LeaveRequest::create([
            ...$request->only(['staff_id', 'type', 'from_date', 'to_date', 'reason']),
            'days'   => $days,
            'status' => 'pending',
        ]);

        return redirect()->route('hrm.leaves.index')->with('success', "Leave request submitted for {$days} day(s).");
    }

    public function approve(int $id)
    {
        $leave = LeaveRequest::with('staff')->findOrFail($id);
        CurrentBranch::guard($leave->staff?->branch_id);

        if ($leave->status === 'approved') {
            return back()->with('error', 'That request is already approved.');
        }

        // Re-checked here, not just at request time: "used" counts approved leave,
        // so two pending requests can each look affordable on their own and only
        // bust the balance once both are approved.
        $year = (int) Carbon::parse($leave->from_date)->year;
        if ($reason = LeaveBalance::refusalReason($leave->staff, $leave->type, (float) $leave->days, $year)) {
            return back()->with('error', $reason);
        }

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

    private function branchStaffRule(): Exists
    {
        return Rule::exists('staff', 'id')->where(
            fn ($q) => CurrentBranch::id() ? $q->where('branch_id', CurrentBranch::id()) : $q
        );
    }
}
