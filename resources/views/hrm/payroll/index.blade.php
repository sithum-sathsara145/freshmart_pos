{{-- hrm/payroll/index.blade.php --}}
@extends('layouts.app')
@section('title','Payroll')
@section('page-title','Payroll')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;gap:8px;margin-bottom:14px;align-items:center">
    <form method="GET" style="display:flex;gap:8px">
        <select name="month" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
            @for($m=1;$m<=12;$m++)
            <option value="{{ $m }}" {{ $month==$m?'selected':'' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
            @endfor
        </select>
        <select name="year" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
            @for($y=now()->year;$y>=now()->year-3;$y--)
            <option value="{{ $y }}" {{ $year==$y?'selected':'' }}>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">View</button>
    </form>
    <form method="POST" action="{{ route('hrm.payroll.generate') }}" style="margin-left:auto">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <button type="submit" style="height:34px;padding:0 14px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-refresh" style="font-size:13px;margin-right:4px"></i>Generate Payroll</button>
    </form>
</div>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Total gross</div><div style="font-size:18px;font-weight:500;color:var(--text)">Rs. {{ number_format($totals['gross']) }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Employee deductions</div><div style="font-size:18px;font-weight:500;color:var(--danger)">Rs. {{ number_format($totals['deduct']) }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Net payroll</div><div style="font-size:18px;font-weight:500;color:var(--success)">Rs. {{ number_format($totals['net']) }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px" title="Net pay plus the employer's EPF (12%) and ETF (3%)"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Cost to employer</div><div style="font-size:18px;font-weight:500;color:var(--primary-text)">Rs. {{ number_format($totals['employer_cost']) }}</div></div>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    {{-- The old table showed "EPF/ETF" as epf_employee + etf in the deductions run,
         which presented an employer contribution as if the employee paid it. --}}
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Staff','Days','Basic earned','OT pay','Allowances','Deductions','EPF (8%)','Net pay','Status',''] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payrolls as $p)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text);font-weight:500">{{ $p->staff?->name }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ rtrim(rtrim(number_format((float) $p->worked_days, 1), '0'), '.') }}</td>
        <td style="padding:9px 12px;color:var(--text-2)" title="Contract Rs. {{ number_format((float) $p->contract_salary, 2) }}">Rs. {{ number_format($p->basic_salary) }}</td>
        <td style="padding:9px 12px;color:var(--primary-text)">Rs. {{ number_format($p->overtime_pay) }}</td>
        <td style="padding:9px 12px;color:var(--success)">Rs. {{ number_format($p->allowances) }}</td>
        <td style="padding:9px 12px;color:var(--danger)">Rs. {{ number_format($p->deductions) }}</td>
        <td style="padding:9px 12px;color:var(--danger)">Rs. {{ number_format($p->epf_employee) }}</td>
        <td style="padding:9px 12px;color:var(--success);font-weight:500">Rs. {{ number_format($p->net_salary) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->status==='paid'?'var(--success-soft)':'var(--warning-soft)' }};color:{{ $p->status==='paid'?'var(--success)':'var(--warning)' }}">{{ ucfirst($p->status) }}</span></td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:4px">
                <a href="{{ route('hrm.payroll.payslip', $p) }}" title="Payslip" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-file-text" style="font-size:12px"></i></a>
                @if($p->status !== 'paid')
                <form method="POST" action="{{ route('hrm.payroll.paid', $p) }}" onsubmit="return confirm('Mark {{ $p->staff?->name }} as paid?')">
                    @csrf @method('PATCH')
                    <button type="submit" title="Mark as paid" style="width:26px;height:26px;background:var(--success-soft);border:.5px solid var(--success-border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--success);cursor:pointer"><i class="ti ti-check" style="font-size:12px"></i></button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="10" style="padding:32px;text-align:center;color:var(--text-4)">No payroll generated yet</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
