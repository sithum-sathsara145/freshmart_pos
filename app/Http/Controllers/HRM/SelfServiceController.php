<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Staff;
use App\Support\AttendanceRecorder;
use App\Support\LeaveBalance;
use App\Support\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * "My HR" — what an employee can see about themselves.
 *
 * The one rule that matters here: every query is scoped by the staff record
 * resolved from the SESSION, never from a route parameter. A payslip id in the
 * URL is only ever used to narrow a query already restricted to `staff_id`, so
 * guessing another employee's id returns 404 rather than their salary.
 *
 * Staff-less logins (the developer account, an admin with no HR record) get a
 * friendly empty state rather than a 500.
 */
class SelfServiceController extends Controller
{
    public function index()
    {
        if (! $staff = $this->staff()) {
            return $this->noRecord();
        }

        $today  = today();
        $year   = (int) $today->year;

        $todayRow = Attendance::where('staff_id', $staff->id)->whereDate('date', $today)->first();

        $monthRows = Attendance::where('staff_id', $staff->id)
            ->whereMonth('date', $today->month)
            ->whereYear('date', $year)
            ->get();

        $summary = [
            'present' => $monthRows->where('status', 'present')->count(),
            'leave'   => $monthRows->where('status', 'leave')->count(),
            'absent'  => $monthRows->where('status', 'absent')->count(),
            'hours'   => round((float) $monthRows->sum('worked_hours'), 1),
            'ot'      => round((float) $monthRows->sum('overtime_hours'), 1),
        ];

        $balances    = LeaveBalance::for($staff, $year);
        $lastPayslip = $this->ownPayrolls($staff)->first();

        return view('hrm.self.index', compact('staff', 'todayRow', 'summary', 'balances', 'lastPayslip', 'year'));
    }

    public function attendance(Request $request)
    {
        if (! $staff = $this->staff()) {
            return $this->noRecord();
        }

        $month = (int) ($request->month ?? now()->month);
        $year  = (int) ($request->year ?? now()->year);

        $rows = Attendance::where('staff_id', $staff->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderByDesc('date')
            ->get();

        $totals = [
            'days'  => $rows->whereIn('status', ['present', 'half_day'])->count(),
            'hours' => round((float) $rows->sum('worked_hours'), 1),
            'ot'    => round((float) $rows->sum('overtime_hours'), 1),
        ];

        return view('hrm.self.attendance', compact('staff', 'rows', 'totals', 'month', 'year'));
    }

    public function payslips()
    {
        if (! $staff = $this->staff()) {
            return $this->noRecord();
        }

        $payslips = $this->ownPayrolls($staff)->get();

        return view('hrm.self.payslips', compact('staff', 'payslips'));
    }

    /**
     * One payslip. The id narrows a query that is ALREADY restricted to this
     * employee, so another person's id simply doesn't match — 404, not 403,
     * because they shouldn't learn it exists.
     */
    public function payslip(Request $request, int $payroll)
    {
        if (! $staff = $this->staff()) {
            return $this->noRecord();
        }

        $row = Payroll::where('staff_id', $staff->id)->where('id', $payroll)->firstOrFail();
        $row->setRelation('staff', $staff->loadMissing('branch'));

        $data = [
            'payroll' => $row,
            'staff'   => $staff,
            'inWords' => PayrollCalculator::amountInWords((float) $row->net_salary),
        ];

        if ($request->input('format') === 'pdf') {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('hrm.payroll.payslip_pdf', $data)
                ->setPaper('A4')
                ->download('payslip-' . $row->year . '-' . $row->month . '.pdf');
        }

        return view('hrm.self.payslip', $data);
    }

    public function leave()
    {
        if (! $staff = $this->staff()) {
            return $this->noRecord();
        }

        $year = (int) now()->year;

        $requests = LeaveRequest::where('staff_id', $staff->id)->latest('from_date')->limit(30)->get();
        $balances = LeaveBalance::for($staff, $year);

        return view('hrm.self.leave', compact('staff', 'requests', 'balances', 'year'));
    }

    /** Apply for own leave. staff_id is taken from the session, never the form. */
    public function storeLeave(Request $request)
    {
        if (! $staff = $this->staff()) {
            return $this->noRecord();
        }

        $request->validate([
            'type'      => 'required|in:annual,sick,casual,other',
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
            'reason'    => 'nullable|string|max:500',
        ]);

        $days = LeaveBalance::countDays($request->from_date, $request->to_date);

        if ($days < 1) {
            return back()->withInput()->with('error', 'That range is entirely holidays — nothing to record.');
        }

        $year = (int) Carbon::parse($request->from_date)->year;

        if ($reason = LeaveBalance::refusalReason($staff, $request->type, $days, $year)) {
            return back()->withInput()->with('error', $reason);
        }

        LeaveRequest::create([
            'staff_id'  => $staff->id,      // from the session, not the request
            'type'      => $request->type,
            'from_date' => $request->from_date,
            'to_date'   => $request->to_date,
            'reason'    => $request->reason,
            'days'      => $days,
            'status'    => 'pending',
        ]);

        return redirect()->route('my.leave')
                         ->with('success', "Request submitted for {$days} day(s) — awaiting approval.");
    }

    /** Withdraw own request, but only while it is still pending. */
    public function destroyLeave(int $leave)
    {
        if (! $staff = $this->staff()) {
            return $this->noRecord();
        }

        $row = LeaveRequest::where('staff_id', $staff->id)->where('id', $leave)->firstOrFail();

        if ($row->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be withdrawn.');
        }

        $row->delete();

        return back()->with('success', 'Request withdrawn.');
    }

    public function clockIn()
    {
        if (! $staff = $this->staff()) {
            return response()->json(['success' => false, 'message' => 'No staff record linked to your login.'], 422);
        }

        $row = AttendanceRecorder::clockIn($staff);

        return response()->json(['success' => true, 'time' => substr((string) $row->time_in, 0, 5)]);
    }

    public function clockOut()
    {
        if (! $staff = $this->staff()) {
            return response()->json(['success' => false, 'message' => 'No staff record linked to your login.'], 422);
        }

        $row = AttendanceRecorder::clockOut($staff);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'No check-in recorded for today.'], 422);
        }

        return response()->json([
            'success' => true,
            'time'    => substr((string) $row->time_out, 0, 5),
            'hours'   => (float) $row->worked_hours,
        ]);
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function staff(): ?Staff
    {
        return auth()->user()?->staff;
    }

    /** Payroll rows for this employee, newest period first. Never unscoped. */
    private function ownPayrolls(Staff $staff)
    {
        return Payroll::where('staff_id', $staff->id)
            ->orderByDesc('year')
            ->orderByDesc('month');
    }

    private function noRecord()
    {
        return response()->view('hrm.self.no-record');
    }
}
