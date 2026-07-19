{{-- hrm/self/attendance.blade.php --}}
@extends('layouts.app')
@section('title','My Attendance')
@section('page-title','My Attendance')
@section('content')
<div style="padding:14px 16px;max-width:900px">

@include('hrm.self._tabs', ['active' => 'attendance'])

<div style="display:flex;gap:8px;margin-bottom:12px;align-items:center;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:6px">
        <select name="month" style="height:32px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
            @for($m = 1; $m <= 12; $m++)
            <option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
            @endfor
        </select>
        <select name="year" style="height:32px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
            @for($y = now()->year; $y >= now()->year - 3; $y--)
            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">View</button>
    </form>
    <div style="margin-left:auto;display:flex;gap:14px;font-size:12px">
        <span style="color:#64748b">Days worked <strong style="color:#e2e8f0">{{ $totals['days'] }}</strong></span>
        <span style="color:#64748b">Hours <strong style="color:#e2e8f0">{{ number_format($totals['hours'], 1) }}</strong></span>
        <span style="color:#64748b">OT <strong style="color:#a5b4fc">{{ number_format($totals['ot'], 1) }}h</strong></span>
    </div>
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Date','In','Out','Hours','OT','Status'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $a)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:8px 12px;color:#e2e8f0">{{ $a->date->format('D, d M') }}</td>
        <td style="padding:8px 12px;color:#64748b">{{ $a->time_in ? substr($a->time_in,0,5) : '—' }}</td>
        <td style="padding:8px 12px;color:#64748b">{{ $a->time_out ? substr($a->time_out,0,5) : '—' }}</td>
        <td style="padding:8px 12px;color:#e2e8f0">{{ $a->worked_hours > 0 ? number_format($a->worked_hours,1).'h' : '—' }}</td>
        <td style="padding:8px 12px;color:#a5b4fc">{{ $a->overtime_hours > 0 ? number_format($a->overtime_hours,1).'h' : '—' }}</td>
        <td style="padding:8px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['present'=>'#14532d','absent'=>'#7f1d1d','leave'=>'#451a03','half_day'=>'#1e3a5f'][$a->status]??'#1e2130' }};color:{{ ['present'=>'#4ade80','absent'=>'#fca5a5','leave'=>'#fb923c','half_day'=>'#60a5fa'][$a->status]??'#94a3b8' }}">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:26px;text-align:center;color:#4a5568">Nothing recorded for this month.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
