{{-- hrm/self/payslips.blade.php --}}
@extends('layouts.app')
@section('title','My Payslips')
@section('page-title','My Payslips')
@section('content')
<div style="padding:14px 16px;max-width:900px">

@include('hrm.self._tabs', ['active' => 'payslips'])

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Period','Days','Basic earned','Overtime','Allowances','Deductions','Net pay','Status',''] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payslips as $p)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text);font-weight:500">{{ $p->periodLabel() }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ rtrim(rtrim(number_format((float) $p->worked_days,1),'0'),'.') }}</td>
        <td style="padding:9px 12px;color:var(--text-2)">Rs. {{ number_format($p->basic_salary) }}</td>
        <td style="padding:9px 12px;color:var(--primary-text)">Rs. {{ number_format($p->overtime_pay) }}</td>
        <td style="padding:9px 12px;color:var(--success)">Rs. {{ number_format($p->allowances) }}</td>
        <td style="padding:9px 12px;color:var(--danger)">Rs. {{ number_format((float) $p->epf_employee + (float) $p->deductions) }}</td>
        <td style="padding:9px 12px;color:var(--success);font-weight:500">Rs. {{ number_format($p->net_salary) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->status==='paid'?'var(--success-soft)':'var(--warning-soft)' }};color:{{ $p->status==='paid'?'var(--success)':'var(--warning)' }}">{{ ucfirst($p->status) }}</span></td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:4px">
                <a href="{{ route('my.payslip', $p->id) }}" title="View" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                <a href="{{ route('my.payslip', ['payroll' => $p->id, 'format' => 'pdf']) }}" title="Download PDF" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-download" style="font-size:12px"></i></a>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="9" style="padding:30px;text-align:center;color:var(--text-4)">No payslips issued yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<div style="font-size:10.5px;color:var(--text-4);margin-top:10px;line-height:1.5">
    Deductions shown are the employee EPF (8%) plus any other deductions. The employer's
    EPF (12%) and ETF (3%) are paid on top of your salary and are not taken from it —
    open a payslip to see them itemised.
</div>
</div>
@endsection
