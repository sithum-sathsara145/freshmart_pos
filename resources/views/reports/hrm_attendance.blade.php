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
        ['Days present',$totals['present'],'var(--success)'],
        ['On leave',$totals['leave'],'var(--warning)'],
        ['Absences',$totals['absent'],'var(--danger)'],
        ['Total hours',number_format($totals['hours'],1),'var(--text)'],
        ['Overtime',number_format($totals['ot'],1).'h','var(--primary-text)'],
    ] as [$l,$v,$c])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:18px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Staff','Job title','Present','Half day','Leave','Absent','Hours','Overtime'] as $h)
        <th style="padding:9px 12px;text-align:{{ in_array($h,['Staff','Job title']) ? 'left' : 'right' }};color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($summary as $r)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--text);font-weight:500">{{ $r['name'] }}</td>
        <td style="padding:8px 12px;color:var(--text-2)">{{ $r['role'] }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--success)">{{ $r['present'] ?: '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--info)">{{ $r['half_day'] ?: '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--warning)">{{ $r['leave'] ?: '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--danger)">{{ $r['absent'] ?: '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--text)">{{ $r['hours'] > 0 ? number_format($r['hours'],1) : '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--primary-text)">{{ $r['ot'] > 0 ? number_format($r['ot'],1).'h' : '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:28px;text-align:center;color:var(--text-4)">No staff in this branch.</td></tr>
    @endforelse
    </tbody>
    @if($summary->count())
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td colspan="2" style="padding:9px 12px;color:var(--text-2);font-weight:500">Total</td>
        <td style="padding:9px 12px;text-align:right;color:var(--success);font-weight:500">{{ $totals['present'] }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--info);font-weight:500">{{ $summary->sum('half_day') }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--warning);font-weight:500">{{ $totals['leave'] }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--danger);font-weight:500">{{ $totals['absent'] }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text);font-weight:500">{{ number_format($totals['hours'],1) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--primary-text);font-weight:500">{{ number_format($totals['ot'],1) }}h</td>
    </tr></tfoot>
    @endif
</table>
</div>
</div>
@endsection
