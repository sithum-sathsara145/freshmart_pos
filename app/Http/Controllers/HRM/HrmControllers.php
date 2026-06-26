<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Holiday;
use App\Models\Appreciation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// =========================================================
// Staff Controller
// =========================================================
class StaffController extends Controller
{
    public function dashboard()
    {
        $branchId = auth()->user()->branch_id;
        $today    = today();

        $stats = [
            'total'      => Staff::where('branch_id', $branchId)->count(),
            'on_duty'    => Attendance::whereDate('date', $today)->where('status', 'present')->count(),
            'on_leave'   => Attendance::whereDate('date', $today)->where('status', 'leave')->count(),
            'absent'     => Attendance::whereDate('date', $today)->where('status', 'absent')->count(),
            'payroll_due'=> Payroll::where('status', 'pending')->whereMonth('created_at', now()->month)->sum('net_salary'),
        ];

        $todayAttendance = Attendance::with('staff')
            ->whereDate('date', $today)
            ->whereHas('staff', fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $byDepartment = Staff::where('branch_id', $branchId)
            ->where('status', 'active')
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->get();

        return view('hrm.dashboard', compact('stats', 'todayAttendance', 'byDepartment'));
    }

    public function index(Request $request)
    {
        $staff = Staff::with('branch')
            ->where('branch_id', auth()->user()->branch_id)
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate(20);

        return view('hrm.staff.index', compact('staff'));
    }

    public function create()
    {
        return view('hrm.staff.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email',
            'role'         => 'required|string',
            'basic_salary' => 'required|numeric|min:0',
            'join_date'    => 'required|date',
            'status'       => 'required|in:active,inactive',
        ]);

        Staff::create([
            ...$request->only(['name', 'phone', 'email', 'address', 'role', 'basic_salary', 'join_date', 'status']),
            'branch_id' => auth()->user()->branch_id,
        ]);

        return redirect()->route('hrm.staff.index')->with('success', 'Staff member added.');
    }

    public function show(Staff $staff)
    {
        $staff->load(['attendance' => fn($q) => $q->latest()->limit(30)]);
        $currentPayroll = Payroll::where('staff_id', $staff->id)
            ->whereMonth('created_at', now()->month)
            ->first();

        return view('hrm.staff.show', compact('staff', 'currentPayroll'));
    }

    public function edit(Staff $staff)
    {
        return view('hrm.staff.edit', compact('staff'));
    }

    public function update(Request $request, Staff $staff)
    {
        $staff->update($request->only(['name', 'phone', 'email', 'address', 'role', 'basic_salary', 'status']));
        return redirect()->route('hrm.staff.show', $staff)->with('success', 'Staff updated.');
    }

    public function destroy(Staff $staff)
    {
        $staff->update(['status' => 'inactive']);
        return redirect()->route('hrm.staff.index')->with('success', 'Staff deactivated.');
    }
}

// =========================================================
// Attendance Controller
// =========================================================
class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $date     = $request->date ?? today()->toDateString();

        $attendance = Attendance::with('staff')
            ->whereDate('date', $date)
            ->whereHas('staff', fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $staff = Staff::where('branch_id', $branchId)->where('status', 'active')->get();

        $stats = [
            'present' => $attendance->where('status', 'present')->count(),
            'absent'  => $attendance->where('status', 'absent')->count(),
            'leave'   => $attendance->where('status', 'leave')->count(),
        ];

        return view('hrm.attendance.index', compact('attendance', 'staff', 'date', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'date'     => 'required|date',
            'status'   => 'required|in:present,absent,leave,half_day',
        ]);

        Attendance::updateOrCreate(
            ['staff_id' => $request->staff_id, 'date' => $request->date],
            [
                'time_in'        => $request->time_in,
                'time_out'       => $request->time_out,
                'worked_hours'   => $request->worked_hours,
                'overtime_hours' => $request->overtime_hours ?? 0,
                'status'         => $request->status,
            ]
        );

        return back()->with('success', 'Attendance recorded.');
    }

    public function checkIn(Request $request)
    {
        $staffId = $request->staff_id ?? auth()->user()->staff?->id;

        Attendance::updateOrCreate(
            ['staff_id' => $staffId, 'date' => today()],
            ['time_in' => now()->toTimeString(), 'status' => 'present']
        );

        return response()->json(['success' => true, 'time' => now()->format('H:i')]);
    }

    public function checkOut(Request $request)
    {
        $staffId    = $request->staff_id ?? auth()->user()->staff?->id;
        $attendance = Attendance::where('staff_id', $staffId)->whereDate('date', today())->first();

        if ($attendance && $attendance->time_in) {
            $in    = \Carbon\Carbon::parse($attendance->time_in);
            $hours = round(now()->diffInMinutes($in) / 60, 2);
            $ot    = max(0, $hours - 8);

            $attendance->update([
                'time_out'       => now()->toTimeString(),
                'worked_hours'   => $hours,
                'overtime_hours' => $ot,
            ]);
        }

        return response()->json(['success' => true, 'time' => now()->format('H:i')]);
    }

    public function create() { return view('hrm.attendance.create'); }
    public function edit(Attendance $attendance) { return view('hrm.attendance.edit', compact('attendance')); }
    public function update(Request $r, Attendance $a) { $a->update($r->only(['time_in','time_out','status'])); return back()->with('success','Updated.'); }
    public function show(Attendance $attendance) { return view('hrm.attendance.show', compact('attendance')); }
    public function destroy(Attendance $attendance) { $attendance->delete(); return back(); }
}

// =========================================================
// Leave Controller
// =========================================================
class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $leaves = LeaveRequest::with('staff')
            ->whereHas('staff', fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('hrm.leaves.index', compact('leaves'));
    }

    public function create()
    {
        $staff = Staff::where('branch_id', auth()->user()->branch_id)->where('status', 'active')->get();
        return view('hrm.leaves.create', compact('staff'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'staff_id'  => 'required|exists:staff,id',
            'type'      => 'required|in:annual,sick,casual,other',
            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',
            'reason'    => 'nullable|string',
        ]);

        $days = \Carbon\Carbon::parse($request->from_date)->diffInDays($request->to_date) + 1;

        LeaveRequest::create([
            ...$request->only(['staff_id', 'type', 'from_date', 'to_date', 'reason']),
            'days'   => $days,
            'status' => 'pending',
        ]);

        return redirect()->route('hrm.leaves.index')->with('success', 'Leave request submitted.');
    }

    public function approve(int $id)
    {
        $leave = LeaveRequest::findOrFail($id);
        $leave->update(['status' => 'approved', 'approved_by' => auth()->id()]);

        // Mark attendance as leave for those days
        $current = \Carbon\Carbon::parse($leave->from_date);
        $end     = \Carbon\Carbon::parse($leave->to_date);
        while ($current <= $end) {
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
        LeaveRequest::findOrFail($id)->update(['status' => 'rejected']);
        return back()->with('success', 'Leave rejected.');
    }

    public function show(LeaveRequest $leave) { return view('hrm.leaves.show', compact('leave')); }
    public function edit(LeaveRequest $leave) { return view('hrm.leaves.edit', compact('leave')); }
    public function update(Request $r, LeaveRequest $l) { return back(); }
    public function destroy(LeaveRequest $leave) { $leave->delete(); return back(); }
}

// =========================================================
// Payroll Controller
// =========================================================
class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year  = $request->year  ?? now()->year;

        $payrolls = Payroll::with('staff')
            ->where('month', $month)
            ->where('year', $year)
            ->whereHas('staff', fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->get();

        $totals = [
            'gross'  => $payrolls->sum(fn($p) => $p->basic_salary + $p->overtime_pay + $p->allowances),
            'deduct' => $payrolls->sum(fn($p) => $p->deductions + $p->epf_employee + $p->etf),
            'net'    => $payrolls->sum('net_salary'),
        ];

        return view('hrm.payroll.index', compact('payrolls', 'totals', 'month', 'year'));
    }

    public function generate(Request $request)
    {
        $month    = $request->month ?? now()->month;
        $year     = $request->year  ?? now()->year;
        $branchId = auth()->user()->branch_id;

        $staffList = Staff::where('branch_id', $branchId)->where('status', 'active')->get();

        DB::beginTransaction();
        try {
            foreach ($staffList as $staff) {
                $attendance = Attendance::where('staff_id', $staff->id)
                    ->whereMonth('date', $month)->whereYear('date', $year)->get();

                $workedDays   = $attendance->where('status', 'present')->count();
                $halfDays     = $attendance->where('status', 'half_day')->count();
                $otHours      = $attendance->sum('overtime_hours');
                $dailySalary  = $staff->basic_salary / 26;
                $basicEarned  = ($workedDays + $halfDays * 0.5) * $dailySalary;
                $otPay        = $otHours * ($dailySalary / 8) * 1.5;
                $epfEmp       = round($basicEarned * 0.08, 2);
                $epfEmr       = round($basicEarned * 0.12, 2);
                $etf          = round($basicEarned * 0.03, 2);
                $net          = $basicEarned + $otPay - $epfEmp - $etf;

                Payroll::updateOrCreate(
                    ['staff_id' => $staff->id, 'month' => $month, 'year' => $year],
                    [
                        'basic_salary'   => round($basicEarned, 2),
                        'overtime_pay'   => round($otPay, 2),
                        'allowances'     => 0,
                        'deductions'     => 0,
                        'epf_employee'   => $epfEmp,
                        'epf_employer'   => $epfEmr,
                        'etf'            => $etf,
                        'net_salary'     => round($net, 2),
                        'status'         => 'pending',
                    ]
                );
            }

            DB::commit();
            return redirect()->route('hrm.payroll.index')->with('success', 'Payroll generated for ' . now()->setMonth($month)->format('F') . " $year.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Payroll generation failed: ' . $e->getMessage());
        }
    }

    public function show(Payroll $payroll) { return view('hrm.payroll.show', compact('payroll')); }
    public function create() { return view('hrm.payroll.create'); }
    public function store(Request $r) { return back(); }
    public function edit(Payroll $p) { return view('hrm.payroll.edit', compact('p')); }
    public function update(Request $r, Payroll $p) { $p->update($r->only(['allowances','deductions','status'])); return back()->with('success','Updated.'); }
    public function destroy(Payroll $p) { $p->delete(); return back(); }
}

// =========================================================
// Holiday Controller
// =========================================================
class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::orderBy('date')->paginate(30);
        $upcoming = Holiday::whereDate('date', '>=', today())->orderBy('date')->limit(5)->get();
        return view('hrm.holidays.index', compact('holidays', 'upcoming'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required', 'date' => 'required|date', 'type' => 'required|in:public,company']);
        Holiday::create($request->only(['name', 'date', 'type']));
        return back()->with('success', 'Holiday added.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return back()->with('success', 'Holiday removed.');
    }

    public function create() { return view('hrm.holidays.create'); }
    public function show(Holiday $h) { return view('hrm.holidays.show', compact('h')); }
    public function edit(Holiday $h) { return view('hrm.holidays.edit', compact('h')); }
    public function update(Request $r, Holiday $h) { $h->update($r->only(['name','date','type'])); return back(); }
}

// =========================================================
// Appreciation Controller
// =========================================================
class AppreciationController extends Controller
{
    public function index()
    {
        $appreciations = Appreciation::with(['staff', 'givenBy'])
            ->whereHas('staff', fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->latest()
            ->paginate(20);

        return view('hrm.appreciations.index', compact('appreciations'));
    }

    public function store(Request $request)
    {
        $request->validate(['staff_id' => 'required|exists:staff,id', 'category' => 'required', 'note' => 'nullable']);
        Appreciation::create([...$request->only(['staff_id','category','note']), 'given_by' => auth()->id()]);
        return back()->with('success', 'Appreciation added!');
    }

    public function create() { return view('hrm.appreciations.create'); }
    public function show(Appreciation $a) { return view('hrm.appreciations.show', compact('a')); }
    public function edit(Appreciation $a) { return view('hrm.appreciations.edit', compact('a')); }
    public function update(Request $r, Appreciation $a) { $a->update($r->only(['category','note'])); return back(); }
    public function destroy(Appreciation $a) { $a->delete(); return back(); }
}
