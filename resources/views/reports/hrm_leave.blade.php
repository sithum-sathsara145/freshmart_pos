{{-- reports/hrm_leave.blade.php --}}
@extends('layouts.app')
@section('title','Leave Summary')
@section('page-title','Reports — Leave Summary')
@section('content')
@php $trim = fn($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.'); @endphp
<div style="padding:14px 16px">

@include('reports.partials.header', [
    'title'  => 'Leave summary ' . $year,
    'icon'   => 'ti-beach',
    'export' => 'hrm_leave',
])

<div style="font-size:11px;color:var(--text-3);margin:-6px 0 12px 2px">
    Entitlement is per calendar year — the year is taken from the start of the selected range.
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead>
        <tr style="border-bottom:.5px solid var(--border)">
            <th rowspan="2" style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px;vertical-align:bottom">Staff</th>
            <th rowspan="2" style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px;vertical-align:bottom">Job title</th>
            @foreach(config('hrm.leave.defaults') as $type => $default)
            <th colspan="2" style="padding:7px 12px 3px;text-align:center;color:var(--text-2);font-weight:500;font-size:11px;border-left:.5px solid var(--border)">{{ ucfirst($type) }}</th>
            @endforeach
        </tr>
        <tr style="border-bottom:.5px solid var(--border)">
            @foreach(config('hrm.leave.defaults') as $type => $default)
            <th style="padding:3px 12px 7px;text-align:right;color:var(--text-4);font-weight:400;font-size:10px;border-left:.5px solid var(--border)">used</th>
            <th style="padding:3px 12px 7px;text-align:right;color:var(--text-4);font-weight:400;font-size:10px">left</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
    @forelse($summary as $entry)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--text);font-weight:500;white-space:nowrap">{{ $entry['staff']->name }}</td>
        <td style="padding:8px 12px;color:var(--text-2);white-space:nowrap">{{ $entry['staff']->role }}</td>
        @foreach($entry['balances'] as $b)
        <td style="padding:8px 12px;text-align:right;color:var(--text-2);border-left:.5px solid var(--surface-3)">{{ $b['used'] > 0 ? $trim($b['used']) : '—' }}</td>
        <td style="padding:8px 12px;text-align:right;color:{{ $b['tracked'] ? ($b['remaining'] <= 0 ? 'var(--danger)' : 'var(--success)') : 'var(--text-4)' }}">
            {{ $b['tracked'] ? $trim($b['remaining']) : 'n/a' }}
        </td>
        @endforeach
    </tr>
    @empty
    <tr><td colspan="10" style="padding:28px;text-align:center;color:var(--text-4)">No staff in this branch.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<div style="font-size:10.5px;color:var(--text-4);margin-top:10px;line-height:1.5">
    “Used” counts approved requests only — pending ones don't consume a balance until approved.
    Other leave is unpaid and uncapped, so it has no remaining figure.
</div>
</div>
@endsection
