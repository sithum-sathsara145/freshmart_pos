{{-- hrm/payroll/index.blade.php --}}
@extends('layouts.app')
@section('title','Payroll')
@section('page-title','Payroll')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;gap:8px;margin-bottom:14px;align-items:center">
    <form method="GET" style="display:flex;gap:8px">
        <select name="month" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
            @for($m=1;$m<=12;$m++)
            <option value="{{ $m }}" {{ $month==$m?'selected':'' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
            @endfor
        </select>
        <select name="year" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
            @for($y=now()->year;$y>=now()->year-3;$y--)
            <option value="{{ $y }}" {{ $year==$y?'selected':'' }}>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" style="height:34px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">View</button>
    </form>
    <form method="POST" action="{{ route('hrm.payroll.generate') }}" style="margin-left:auto">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <button type="submit" style="height:34px;padding:0 14px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-refresh" style="font-size:13px;margin-right:4px"></i>Generate Payroll</button>
    </form>
</div>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Total gross</div><div style="font-size:18px;font-weight:500;color:#e2e8f0">Rs. {{ number_format($totals['gross']) }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Deductions</div><div style="font-size:18px;font-weight:500;color:#f87171">Rs. {{ number_format($totals['deduct']) }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Net payroll</div><div style="font-size:18px;font-weight:500;color:#4ade80">Rs. {{ number_format($totals['net']) }}</div></div>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Staff','Basic','OT pay','Allowances','Deductions','EPF/ETF','Net pay','Status'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payrolls as $p)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $p->staff?->name }}</td>
        <td style="padding:9px 12px;color:#94a3b8">Rs. {{ number_format($p->basic_salary) }}</td>
        <td style="padding:9px 12px;color:#a5b4fc">Rs. {{ number_format($p->overtime_pay) }}</td>
        <td style="padding:9px 12px;color:#4ade80">Rs. {{ number_format($p->allowances) }}</td>
        <td style="padding:9px 12px;color:#f87171">Rs. {{ number_format($p->deductions) }}</td>
        <td style="padding:9px 12px;color:#64748b">Rs. {{ number_format($p->epf_employee + $p->etf) }}</td>
        <td style="padding:9px 12px;color:#4ade80;font-weight:500">Rs. {{ number_format($p->net_salary) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->status==='paid'?'#14532d':'#451a03' }};color:{{ $p->status==='paid'?'#4ade80':'#fb923c' }}">{{ ucfirst($p->status) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:32px;text-align:center;color:#4a5568">No payroll generated yet</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
