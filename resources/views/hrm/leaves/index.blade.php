{{-- hrm/leaves/index.blade.php --}}
@extends('layouts.app')
@section('title','Leave Requests')
@section('page-title','Leave Requests')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <a href="{{ route('hrm.leaves.create') }}" style="height:34px;padding:0 14px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>New Leave Request
    </a>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Staff','Type','From','To','Days','Reason','Status','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($leaves as $l)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text);font-weight:500">{{ $l->staff?->name }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:var(--primary-soft);color:var(--primary-text)">{{ ucfirst($l->type) }}</span></td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $l->from_date?->format('d M') }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $l->to_date?->format('d M Y') }}</td>
        <td style="padding:9px 12px;text-align:center;color:var(--text)">{{ $l->days }}</td>
        <td style="padding:9px 12px;color:var(--text-2);font-size:11px">{{ Str::limit($l->reason,22) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['pending'=>'var(--warning-soft)','approved'=>'var(--success-soft)','rejected'=>'var(--danger-soft)'][$l->status]??'var(--surface-2)' }};color:{{ ['pending'=>'var(--warning)','approved'=>'var(--success)','rejected'=>'var(--danger-text)'][$l->status]??'var(--text-2)' }}">{{ ucfirst($l->status) }}</span></td>
        <td style="padding:9px 12px">
            @if($l->status === 'pending')
            <div style="display:flex;gap:3px">
                <form method="POST" action="{{ route('hrm.leaves.approve',$l->id) }}">@csrf @method('PATCH')<button type="submit" style="height:26px;padding:0 8px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:5px;font-size:11px;cursor:pointer">Approve</button></form>
                <form method="POST" action="{{ route('hrm.leaves.reject',$l->id) }}">@csrf @method('PATCH')<button type="submit" style="height:26px;padding:0 8px;background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:5px;font-size:11px;cursor:pointer">Reject</button></form>
            </div>
            @else
            <span style="color:var(--text-4);font-size:11px">—</span>
            @endif
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:32px;text-align:center;color:var(--text-4)">No leave requests</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $leaves->links() }}</div>
</div>
@endsection
