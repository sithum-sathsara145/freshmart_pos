{{-- hrm/payroll/payslip.blade.php --}}
@extends('layouts.app')
@section('title','Payslip')
@section('page-title','Payslip — '.$staff?->name)
@section('content')
@php
    $row  = fn($label, $value, $colour = '#e2e8f0') =>
        '<div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">'
        .'<span style="color:#94a3b8">'.$label.'</span>'
        .'<span style="color:'.$colour.'">'.$value.'</span></div>';
    $money = fn($v) => number_format((float) $v, 2);
@endphp
<div style="padding:14px 16px;max-width:900px">

<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('hrm.payroll.index', ['month' => $payroll->month, 'year' => $payroll->year]) }}"
       style="height:34px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none">
       <i class="ti ti-arrow-left" style="font-size:14px"></i> Back to payroll</a>
    <a href="{{ route('hrm.payroll.payslip', ['payroll' => $payroll, 'format' => 'pdf']) }}"
       style="height:34px;padding:0 14px;background:#312e81;border:.5px solid #534AB7;border-radius:6px;color:#a5b4fc;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none;margin-left:auto">
       <i class="ti ti-file-type-pdf" style="font-size:14px"></i> Download PDF</a>
    @can('hrm.payroll.manage')
    @if($payroll->status !== 'paid')
    <form method="POST" action="{{ route('hrm.payroll.paid', $payroll) }}">
        @csrf @method('PATCH')
        <button type="submit" style="height:34px;padding:0 14px;background:#14532d;border:.5px solid #166534;border-radius:6px;color:#4ade80;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:5px">
            <i class="ti ti-check" style="font-size:14px"></i> Mark as paid</button>
    </form>
    @endif
    @endcan
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:18px">

    <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:.5px solid #2a2d3a;padding-bottom:12px;margin-bottom:14px">
        <div>
            <div style="font-size:15px;font-weight:500;color:#e2e8f0">{{ $staff?->name }}</div>
            <div style="font-size:11px;color:#64748b;margin-top:3px">{{ $staff?->role }} · {{ $staff?->branch?->name }}</div>
        </div>
        <div style="text-align:right">
            <div style="font-size:13px;color:#e2e8f0">{{ $payroll->periodLabel() }}</div>
            <span style="font-size:10px;padding:2px 8px;border-radius:10px;margin-top:4px;display:inline-block;background:{{ $payroll->status === 'paid' ? '#14532d' : '#451a03' }};color:{{ $payroll->status === 'paid' ? '#4ade80' : '#fb923c' }}">
                {{ ucfirst($payroll->status) }}{{ $payroll->paid_at ? ' · '.$payroll->paid_at->format('d M Y') : '' }}
            </span>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div>
            <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Earnings</div>
            {!! $row('Basic salary <span style="color:#4a5568;font-size:10px">(contract '.$money($payroll->contract_salary).')</span>', 'Rs. '.$money($payroll->basic_salary)) !!}
            {!! $row('Overtime <span style="color:#4a5568;font-size:10px">('.rtrim(rtrim(number_format((float) $payroll->ot_hours, 2), '0'), '.').' hrs)</span>', 'Rs. '.$money($payroll->overtime_pay)) !!}
            {!! $row('Allowances', 'Rs. '.$money($payroll->allowances)) !!}
            {!! $row('<strong>Gross</strong>', '<strong>Rs. '.$money($payroll->gross_salary).'</strong>') !!}
        </div>
        <div>
            <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Deductions</div>
            {!! $row('EPF employee (8%)', '− Rs. '.$money($payroll->epf_employee), '#fca5a5') !!}
            {!! $row('Other deductions', '− Rs. '.$money($payroll->deductions), '#fca5a5') !!}
            {!! $row('Days paid', rtrim(rtrim(number_format((float) $payroll->worked_days, 1), '0'), '.')) !!}
            {!! $row('<strong>Total deductions</strong>', '<strong>− Rs. '.$money((float) $payroll->epf_employee + (float) $payroll->deductions).'</strong>', '#fca5a5') !!}
        </div>
    </div>

    <div style="margin-top:16px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:7px;padding:14px;display:flex;justify-content:space-between;align-items:center">
        <div>
            <div style="font-size:11px;color:#64748b">NET PAY</div>
            <div style="font-size:11px;color:#4a5568;font-style:italic;margin-top:3px">{{ $inWords }}</div>
        </div>
        <div style="font-size:22px;font-weight:500;color:#4ade80">Rs. {{ $money($payroll->net_salary) }}</div>
    </div>

    <div style="margin-top:16px">
        <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Employer contributions</div>
        {!! $row('EPF employer (12%)', 'Rs. '.$money($payroll->epf_employer)) !!}
        {!! $row('ETF (3%)', 'Rs. '.$money($payroll->etf)) !!}
        {!! $row('<strong>Total cost to employer</strong>', '<strong>Rs. '.$money($payroll->employerCost()).'</strong>') !!}
        <div style="font-size:10.5px;color:#4a5568;margin-top:8px;line-height:1.5">
            EPF employer and ETF are the shop's statutory contributions — they are paid on top of the
            salary and are never deducted from the employee.
        </div>
    </div>

</div>
</div>
@endsection
