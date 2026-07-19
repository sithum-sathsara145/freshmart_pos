{{-- hrm/self/payslips.blade.php --}}
@extends('layouts.app')
@section('title','My Payslips')
@section('page-title','My Payslips')
@section('content')
<div style="padding:14px 16px;max-width:900px">

@include('hrm.self._tabs', ['active' => 'payslips'])

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Period','Days','Basic earned','Overtime','Allowances','Deductions','Net pay','Status',''] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payslips as $p)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $p->periodLabel() }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ rtrim(rtrim(number_format((float) $p->worked_days,1),'0'),'.') }}</td>
        <td style="padding:9px 12px;color:#94a3b8">Rs. {{ number_format($p->basic_salary) }}</td>
        <td style="padding:9px 12px;color:#a5b4fc">Rs. {{ number_format($p->overtime_pay) }}</td>
        <td style="padding:9px 12px;color:#4ade80">Rs. {{ number_format($p->allowances) }}</td>
        <td style="padding:9px 12px;color:#f87171">Rs. {{ number_format((float) $p->epf_employee + (float) $p->deductions) }}</td>
        <td style="padding:9px 12px;color:#4ade80;font-weight:500">Rs. {{ number_format($p->net_salary) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->status==='paid'?'#14532d':'#451a03' }};color:{{ $p->status==='paid'?'#4ade80':'#fb923c' }}">{{ ucfirst($p->status) }}</span></td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:4px">
                <a href="{{ route('my.payslip', $p->id) }}" title="View" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                <a href="{{ route('my.payslip', ['payroll' => $p->id, 'format' => 'pdf']) }}" title="Download PDF" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-download" style="font-size:12px"></i></a>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="9" style="padding:30px;text-align:center;color:#4a5568">No payslips issued yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<div style="font-size:10.5px;color:#4a5568;margin-top:10px;line-height:1.5">
    Deductions shown are the employee EPF (8%) plus any other deductions. The employer's
    EPF (12%) and ETF (3%) are paid on top of your salary and are not taken from it —
    open a payslip to see them itemised.
</div>
</div>
@endsection
