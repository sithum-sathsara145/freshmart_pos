{{-- hrm/self/attendance.blade.php --}}
@extends('layouts.app')
@section('title','My Attendance')
@section('page-title','My Attendance')
@section('content')
<div style="padding:14px 16px;max-width:900px">

@include('hrm.self._tabs', ['active' => 'attendance'])

<div style="display:flex;gap:8px;margin-bottom:12px;align-items:center;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:6px">
        <select name="month" style="height:32px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
            @for($m = 1; $m <= 12; $m++)
            <option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
            @endfor
        </select>
        <select name="year" style="height:32px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
            @for($y = now()->year; $y >= now()->year - 3; $y--)
            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">View</button>
    </form>
    <div style="margin-left:auto;display:flex;gap:14px;font-size:12px">
        <span style="color:var(--text-3)">Days worked <strong style="color:var(--text)">{{ $totals['days'] }}</strong></span>
        <span style="color:var(--text-3)">Hours <strong style="color:var(--text)">{{ number_format($totals['hours'], 1) }}</strong></span>
        <span style="color:var(--text-3)">OT <strong style="color:var(--primary-text)">{{ number_format($totals['ot'], 1) }}h</strong></span>
    </div>
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Date','In','Out','Hours','OT','Status'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $a)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--text)">{{ $a->date->format('D, d M') }}</td>
        <td style="padding:8px 12px;color:var(--text-3)">{{ $a->time_in ? substr($a->time_in,0,5) : '—' }}</td>
        <td style="padding:8px 12px;color:var(--text-3)">{{ $a->time_out ? substr($a->time_out,0,5) : '—' }}</td>
        <td style="padding:8px 12px;color:var(--text)">{{ $a->worked_hours > 0 ? number_format($a->worked_hours,1).'h' : '—' }}</td>
        <td style="padding:8px 12px;color:var(--primary-text)">{{ $a->overtime_hours > 0 ? number_format($a->overtime_hours,1).'h' : '—' }}</td>
        <td style="padding:8px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['present'=>'var(--success-soft)','absent'=>'var(--danger-soft)','leave'=>'var(--warning-soft)','half_day'=>'var(--info-soft)'][$a->status]??'var(--surface-2)' }};color:{{ ['present'=>'var(--success)','absent'=>'var(--danger-text)','leave'=>'var(--warning)','half_day'=>'var(--info)'][$a->status]??'var(--text-2)' }}">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:26px;text-align:center;color:var(--text-4)">Nothing recorded for this month.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
