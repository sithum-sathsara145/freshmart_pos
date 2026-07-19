{{-- reports/hrm_attendance.blade.php --}}
@extends('layouts.app')
@section('title','Attendance Summary')
@section('page-title','Reports — Attendance Summary')
@section('content')
<div style="padding:14px 16px">

@include('reports.partials.header', [
    'title'  => 'Attendance summary',
    'icon'   => 'ti-calendar-check',
    'export' => 'hrm_attendance',
])

<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:14px">
    @foreach([
        ['Days present',$totals['present'],'#4ade80'],
        ['On leave',$totals['leave'],'#fb923c'],
        ['Absences',$totals['absent'],'#f87171'],
        ['Total hours',number_format($totals['hours'],1),'#e2e8f0'],
        ['Overtime',number_format($totals['ot'],1).'h','#a5b4fc'],
    ] as [$l,$v,$c])
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:#64748b;margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:18px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Staff','Job title','Present','Half day','Leave','Absent','Hours','Overtime'] as $h)
        <th style="padding:9px 12px;text-align:{{ in_array($h,['Staff','Job title']) ? 'left' : 'right' }};color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($summary as $r)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:8px 12px;color:#e2e8f0;font-weight:500">{{ $r['name'] }}</td>
        <td style="padding:8px 12px;color:#94a3b8">{{ $r['role'] }}</td>
        <td style="padding:8px 12px;text-align:right;color:#4ade80">{{ $r['present'] ?: '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:#60a5fa">{{ $r['half_day'] ?: '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:#fb923c">{{ $r['leave'] ?: '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:#f87171">{{ $r['absent'] ?: '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:#e2e8f0">{{ $r['hours'] > 0 ? number_format($r['hours'],1) : '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:#a5b4fc">{{ $r['ot'] > 0 ? number_format($r['ot'],1).'h' : '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:28px;text-align:center;color:#4a5568">No staff in this branch.</td></tr>
    @endforelse
    </tbody>
    @if($summary->count())
    <tfoot><tr style="border-top:.5px solid #2a2d3a;background:#0f1117">
        <td colspan="2" style="padding:9px 12px;color:#94a3b8;font-weight:500">Total</td>
        <td style="padding:9px 12px;text-align:right;color:#4ade80;font-weight:500">{{ $totals['present'] }}</td>
        <td style="padding:9px 12px;text-align:right;color:#60a5fa;font-weight:500">{{ $summary->sum('half_day') }}</td>
        <td style="padding:9px 12px;text-align:right;color:#fb923c;font-weight:500">{{ $totals['leave'] }}</td>
        <td style="padding:9px 12px;text-align:right;color:#f87171;font-weight:500">{{ $totals['absent'] }}</td>
        <td style="padding:9px 12px;text-align:right;color:#e2e8f0;font-weight:500">{{ number_format($totals['hours'],1) }}</td>
        <td style="padding:9px 12px;text-align:right;color:#a5b4fc;font-weight:500">{{ number_format($totals['ot'],1) }}h</td>
    </tr></tfoot>
    @endif
</table>
</div>
</div>
@endsection
