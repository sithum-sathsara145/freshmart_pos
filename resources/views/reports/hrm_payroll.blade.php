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
        ['Gross pay',$totals['gross'],'var(--text)','Basic earned + overtime + allowances'],
        ['Employee deductions',$totals['epf_emp'] + $totals['deduct'],'var(--danger)','EPF 8% plus other deductions'],
        ['Net paid',$totals['net'],'var(--success)','What employees actually receive'],
        ['Cost to employer',$totals['employer'],'var(--primary-text)','Gross plus employer EPF 12% and ETF 3%'],
    ] as [$l,$v,$c,$hint])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px" title="{{ $hint }}">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:17px;font-weight:500;color:{{ $c }}">Rs. {{ number_format($v) }}</div>
    </div>
    @endforeach
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:12px;min-width:1000px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Staff','Period','Contract','Basic earned','Overtime','Allowances','Gross','EPF 8%','Deductions','Net pay','Employer cost','Status'] as $i => $h)
        <th style="padding:9px 10px;text-align:{{ $i < 2 ? 'left' : ($h === 'Status' ? 'center' : 'right') }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payrolls as $p)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 10px;color:var(--text);font-weight:500;white-space:nowrap">{{ $p->staff?->name ?? '—' }}</td>
        <td style="padding:8px 10px;color:var(--text-3);white-space:nowrap">{{ $p->periodLabel() }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text-3)">{{ $money($p->contract_salary) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text-2)">{{ $money($p->basic_salary) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--primary-text)">{{ $money($p->overtime_pay) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--success)">{{ $money($p->allowances) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text)">{{ $money($p->gross_salary) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--danger)">{{ $money($p->epf_employee) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--danger)">{{ $money($p->deductions) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--success);font-weight:500">{{ $money($p->net_salary) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--primary-text)">{{ $money($p->employerCost()) }}</td>
        <td style="padding:8px 10px;text-align:center"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->status==='paid'?'var(--success-soft)':'var(--warning-soft)' }};color:{{ $p->status==='paid'?'var(--success)':'var(--warning)' }}">{{ ucfirst($p->status) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="12" style="padding:28px;text-align:center;color:var(--text-4)">No payroll generated for this period.</td></tr>
    @endforelse
    </tbody>
    @if($payrolls->count())
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td colspan="2" style="padding:9px 10px;color:var(--text-2);font-weight:500">Total ({{ $payrolls->count() }})</td>
        <td style="padding:9px 10px;text-align:right;color:var(--text-3)">{{ $money($payrolls->sum('contract_salary')) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--text-2)">{{ $money($payrolls->sum('basic_salary')) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--primary-text)">{{ $money($payrolls->sum('overtime_pay')) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--success)">{{ $money($payrolls->sum('allowances')) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--text);font-weight:500">{{ $money($totals['gross']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--danger)">{{ $money($totals['epf_emp']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--danger)">{{ $money($totals['deduct']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--success);font-weight:500">{{ $money($totals['net']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--primary-text);font-weight:500">{{ $money($totals['employer']) }}</td>
        <td></td>
    </tr></tfoot>
    @endif
</table>
</div>

<div style="font-size:10.5px;color:var(--text-4);margin-top:10px;line-height:1.5">
    EPF 8% is the employee's contribution and is deducted from pay. The employer's EPF (12%)
    and ETF (3%) are a cost to the shop and are not deducted — they are folded into
    “Employer cost” rather than shown as deductions.
</div>
</div>
@endsection
