<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Staff;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) ($request->month ?? now()->month);
        $year  = (int) ($request->year ?? now()->year);

        $payrolls = Payroll::with('staff')
            ->where('month', $month)
            ->where('year', $year)
            ->whereHas('staff', fn ($q) => $q->whereBranch(CurrentBranch::id()))
            ->get();

        $totals = [
            'gross'  => $payrolls->sum(fn ($p) => (float) $p->basic_salary + (float) $p->overtime_pay + (float) $p->allowances),
            // Employee-side only: employer EPF and ETF are a cost to the shop, not a
            // deduction from anyone's pay.
            'deduct'         => $payrolls->sum(fn ($p) => (float) $p->deductions + (float) $p->epf_employee),
            'net'            => $payrolls->sum('net_salary'),
            'employer_cost'  => $payrolls->sum(fn ($p) => $p->employerCost()),
        ];

        return view('hrm.payroll.index', compact('payrolls', 'totals', 'month', 'year'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year'  => 'nullable|integer|min:2000|max:2100',
        ]);

        $month    = (int) ($request->month ?? now()->month);
        $year     = (int) ($request->year ?? now()->year);
        $branchId = CurrentBranch::id();

        $staffList = Staff::whereBranch($branchId)->where('status', 'active')->get();

        $generated = 0;
        $skipped   = 0;

        DB::beginTransaction();
        try {
            foreach ($staffList as $staff) {
                $existing = Payroll::where('staff_id', $staff->id)
                    ->where('month', $month)->where('year', $year)->first();

                // Never rewrite a payroll that has already been paid out.
                if ($existing && $existing->status === 'paid') {
                    $skipped++;
                    continue;
                }

                $figures = \App\Support\PayrollCalculator::for($staff, $month, $year, [
                    // Manual allowances/deductions survive a regenerate — the old code
                    // hardcoded them back to 0 every run.
                    'allowances' => (float) ($existing->allowances ?? 0),
                    'deductions' => (float) ($existing->deductions ?? 0),
                ]);

                Payroll::updateOrCreate(
                    ['staff_id' => $staff->id, 'month' => $month, 'year' => $year],
                    $figures
                );

                $generated++;
            }

            DB::commit();

            $msg = "Payroll generated for {$generated} staff — " . now()->setMonth($month)->format('F') . " {$year}.";
            if ($skipped) {
                $msg .= " {$skipped} already paid and left untouched.";
            }

            return redirect()->route('hrm.payroll.index', ['month' => $month, 'year' => $year])
                             ->with('success', $msg);

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Payroll generation failed: ' . $e->getMessage());
        }
    }

    /** Edit allowances/deductions — net is always recomputed, never trusted from the form. */
    public function update(Request $request, Payroll $payroll)
    {
        $payroll->load('staff');
        CurrentBranch::guard($payroll->staff?->branch_id);

        $request->validate([
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'status'     => 'nullable|in:pending,paid',
        ]);

        $payroll->allowances = (float) ($request->allowances ?? 0);
        $payroll->deductions = (float) ($request->deductions ?? 0);

        \App\Support\PayrollCalculator::applyTotals($payroll);

        if ($request->filled('status')) {
            $payroll->status = $request->status;
            if ($request->status === 'paid' && ! $payroll->paid_at) {
                $payroll->paid_at = now();
            }
        }

        $payroll->save();

        return back()->with('success', 'Payroll updated.');
    }

    public function markPaid(Payroll $payroll)
    {
        $payroll->load('staff');
        CurrentBranch::guard($payroll->staff?->branch_id);

        $payroll->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', "Marked as paid for {$payroll->staff?->name}.");
    }

    public function payslip(Request $request, Payroll $payroll)
    {
        $payroll->load('staff.branch');
        CurrentBranch::guard($payroll->staff?->branch_id);

        $data = [
            'payroll' => $payroll,
            'staff'   => $payroll->staff,
            'inWords' => \App\Support\PayrollCalculator::amountInWords((float) $payroll->net_salary),
        ];

        if ($request->input('format') === 'pdf') {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('hrm.payroll.payslip_pdf', $data)
                ->setPaper('A4')
                ->download('payslip-' . $payroll->staff?->id . '-' . $payroll->year . '-' . $payroll->month . '.pdf');
        }

        return view('hrm.payroll.payslip', $data);
    }

    public function destroy(Payroll $payroll)
    {
        $payroll->load('staff');
        CurrentBranch::guard($payroll->staff?->branch_id);

        if ($payroll->status === 'paid') {
            return back()->with('error', 'This payroll has been paid — it cannot be deleted.');
        }

        $payroll->delete();

        return back()->with('success', 'Payroll entry removed.');
    }
}
