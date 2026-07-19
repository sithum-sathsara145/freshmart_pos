<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\Staff;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function dashboard()
    {
        $branchId = CurrentBranch::id();
        $today    = today();

        // One scoped query feeds both the tiles and the list — the tiles used to count
        // every branch's attendance while the staff total was scoped, so they disagreed.
        $todayAttendance = Attendance::with('staff')
            ->whereDate('date', $today)
            ->whereHas('staff', fn ($q) => $q->whereBranch($branchId))
            ->get();

        $stats = [
            'total'    => Staff::whereBranch($branchId)->count(),
            'on_duty'  => $todayAttendance->where('status', 'present')->count(),
            'on_leave' => $todayAttendance->where('status', 'leave')->count(),
            'absent'   => $todayAttendance->where('status', 'absent')->count(),

            // Payroll rows carry their own month/year — created_at is when the row was
            // generated, which is a different month whenever payroll is run in arrears.
            'payroll_due' => Payroll::where('status', 'pending')
                ->where('month', $today->month)
                ->where('year', $today->year)
                ->whereHas('staff', fn ($q) => $q->whereBranch($branchId))
                ->sum('net_salary'),
        ];

        $byDepartment = Staff::whereBranch($branchId)
            ->where('status', 'active')
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->get();

        return view('hrm.dashboard', compact('stats', 'todayAttendance', 'byDepartment'));
    }

    public function index(Request $request)
    {
        $staff = Staff::with(['branch', 'user'])
            ->whereBranch(CurrentBranch::id())
            // Grouped: an ungrouped orWhere would escape the branch constraint above and
            // leak other branches' staff whenever someone searched by phone.
            ->when($request->search, fn ($q, $search) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->when($request->role, fn ($q) => $q->where('role', $request->role))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('hrm.staff.index', compact('staff'));
    }

    public function create()
    {
        return view('hrm.staff.create', ['staff' => null] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if (! $branchId = CurrentBranch::requireId()) {
            return back()->withInput()->with('error', CurrentBranch::pickBranchMessage());
        }

        Staff::create($data + ['branch_id' => $branchId]);

        return redirect()->route('hrm.staff.index')->with('success', 'Staff member added.');
    }

    public function show(Staff $staff)
    {
        CurrentBranch::guard($staff->branch_id);

        $staff->load(['user.roles', 'branch', 'attendance' => fn ($q) => $q->latest('date')->limit(30)]);

        // Look up by the payroll period, not the row's creation date.
        $currentPayroll = Payroll::where('staff_id', $staff->id)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->first();

        return view('hrm.staff.show', compact('staff', 'currentPayroll'));
    }

    public function edit(Staff $staff)
    {
        CurrentBranch::guard($staff->branch_id);

        return view('hrm.staff.edit', compact('staff') + $this->formData($staff));
    }

    public function update(Request $request, Staff $staff)
    {
        CurrentBranch::guard($staff->branch_id);

        // store() validated but update() did not, so anything could be written here.
        $data = $this->validated($request, $staff);
        unset($data['join_date']);        // the edit form shows it read-only

        $staff->update($data);

        return redirect()->route('hrm.staff.show', $staff)->with('success', 'Staff updated.');
    }

    public function destroy(Staff $staff)
    {
        CurrentBranch::guard($staff->branch_id);

        $staff->update(['status' => 'inactive']);

        return redirect()->route('hrm.staff.index')->with('success', 'Staff deactivated.');
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function validated(Request $request, ?Staff $staff = null): array
    {
        return $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:150',
            'address'      => 'nullable|string',
            'role'         => ['required', 'string', Rule::in(array_keys(config('hrm.job_titles')))],
            'basic_salary' => 'required|numeric|min:0',
            'join_date'    => 'required|date',
            'status'       => 'required|in:active,inactive',

            // staff.user_id is UNIQUE — without ignore() a re-save of the same record,
            // or a second staff row pointing at one account, would be a 500 not a message.
            'user_id'      => [
                'nullable',
                'exists:users,id',
                Rule::unique('staff', 'user_id')->ignore($staff?->id),
            ],
        ]);
    }

    /** Login accounts selectable on the staff form, plus the job-title list. */
    private function formData(?Staff $staff = null): array
    {
        $branchId = CurrentBranch::id();

        $linkedElsewhere = Staff::whereNotNull('user_id')
            ->when($staff, fn ($q) => $q->where('id', '!=', $staff->id))
            ->pluck('user_id');

        $actor = auth()->user();

        return [
            'jobTitles' => config('hrm.job_titles'),
            'users'     => \App\Models\User::query()
                ->whereBranch($branchId)
                ->whereNotIn('id', $linkedElsewhere)
                // super_admin stays invisible to everyone else, here as everywhere.
                ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereDoesntHave(
                    'roles', fn ($r) => $r->where('name', \App\Models\Role::SUPER_ADMIN)
                ))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ];
    }
}
