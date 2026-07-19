{{-- reports/hrm_payroll.blade.php — the classic salary sheet --}}
@extends('layouts.app')
@section('title','Payroll Register')
@section('page-title','Reports — Payroll Register')
@section('content')
@php $money = fn($v) => number_format((float) $v, 2); @endphp
<div style="padding:14px 16px">

@include('reports.partials.header', [
    'title'  => 'Payroll register',
    'icon'   => 'ti-report-money',
    'export' => 'hrm_payroll',
])

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    @foreach([
        ['Gross pay',$totals['gross'],'#e2e8f0','Basic earned + overtime + allowances'],
        ['Employee deductions',$totals['epf_emp'] + $totals['deduct'],'#f87171','EPF 8% plus other deductions'],
        ['Net paid',$totals['net'],'#4ade80','What employees actually receive'],
        ['Cost to employer',$totals['employer'],'#a5b4fc','Gross plus employer EPF 12% and ETF 3%'],
    ] as [$l,$v,$c,$hint])
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px" title="{{ $hint }}">
        <div style="font-size:10px;color:#64748b;margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:17px;font-weight:500;color:{{ $c }}">Rs. {{ number_format($v) }}</div>
    </div>
    @endforeach
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:12px;min-width:1000px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Staff','Period','Contract','Basic earned','Overtime','Allowances','Gross','EPF 8%','Deductions','Net pay','Employer cost','Status'] as $i => $h)
        <th style="padding:9px 10px;text-align:{{ $i < 2 ? 'left' : ($h === 'Status' ? 'center' : 'right') }};color:#64748b;font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payrolls as $p)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:8px 10px;color:#e2e8f0;font-weight:500;white-space:nowrap">{{ $p->staff?->name ?? '—' }}</td>
        <td style="padding:8px 10px;color:#64748b;white-space:nowrap">{{ $p->periodLabel() }}</td>
        <td style="padding:8px 10px;text-align:right;color:#64748b">{{ $money($p->contract_salary) }}</td>
        <td style="padding:8px 10px;text-align:right;color:#94a3b8">{{ $money($p->basic_salary) }}</td>
        <td style="padding:8px 10px;text-align:right;color:#a5b4fc">{{ $money($p->overtime_pay) }}</td>
        <td style="padding:8px 10px;text-align:right;color:#4ade80">{{ $money($p->allowances) }}</td>
        <td style="padding:8px 10px;text-align:right;color:#e2e8f0">{{ $money($p->gross_salary) }}</td>
        <td style="padding:8px 10px;text-align:right;color:#f87171">{{ $money($p->epf_employee) }}</td>
        <td style="padding:8px 10px;text-align:right;color:#f87171">{{ $money($p->deductions) }}</td>
        <td style="padding:8px 10px;text-align:right;color:#4ade80;font-weight:500">{{ $money($p->net_salary) }}</td>
        <td style="padding:8px 10px;text-align:right;color:#a5b4fc">{{ $money($p->employerCost()) }}</td>
        <td style="padding:8px 10px;text-align:center"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->status==='paid'?'#14532d':'#451a03' }};color:{{ $p->status==='paid'?'#4ade80':'#fb923c' }}">{{ ucfirst($p->status) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="12" style="padding:28px;text-align:center;color:#4a5568">No payroll generated for this period.</td></tr>
    @endforelse
    </tbody>
    @if($payrolls->count())
    <tfoot><tr style="border-top:.5px solid #2a2d3a;background:#0f1117">
        <td colspan="2" style="padding:9px 10px;color:#94a3b8;font-weight:500">Total ({{ $payrolls->count() }})</td>
        <td style="padding:9px 10px;text-align:right;color:#64748b">{{ $money($payrolls->sum('contract_salary')) }}</td>
        <td style="padding:9px 10px;text-align:right;color:#94a3b8">{{ $money($payrolls->sum('basic_salary')) }}</td>
        <td style="padding:9px 10px;text-align:right;color:#a5b4fc">{{ $money($payrolls->sum('overtime_pay')) }}</td>
        <td style="padding:9px 10px;text-align:right;color:#4ade80">{{ $money($payrolls->sum('allowances')) }}</td>
        <td style="padding:9px 10px;text-align:right;color:#e2e8f0;font-weight:500">{{ $money($totals['gross']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:#f87171">{{ $money($totals['epf_emp']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:#f87171">{{ $money($totals['deduct']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:#4ade80;font-weight:500">{{ $money($totals['net']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:#a5b4fc;font-weight:500">{{ $money($totals['employer']) }}</td>
        <td></td>
    </tr></tfoot>
    @endif
</table>
</div>

<div style="font-size:10.5px;color:#4a5568;margin-top:10px;line-height:1.5">
    EPF 8% is the employee's contribution and is deducted from pay. The employer's EPF (12%)
    and ETF (3%) are a cost to the shop and are not deducted — they are folded into
    “Employer cost” rather than shown as deductions.
</div>
</div>
@endsection
