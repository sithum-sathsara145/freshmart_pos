{{-- hrm/leaves/index.blade.php --}}
@extends('layouts.app')
@section('title','Leave Requests')
@section('page-title','Leave Requests')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <a href="{{ route('hrm.leaves.create') }}" style="height:34px;padding:0 14px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>New Leave Request
    </a>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Staff','Type','From','To','Days','Reason','Status','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($leaves as $l)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $l->staff?->name }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#312e81;color:#a5b4fc">{{ ucfirst($l->type) }}</span></td>
        <td style="padding:9px 12px;color:#64748b">{{ $l->from_date?->format('d M') }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $l->to_date?->format('d M Y') }}</td>
        <td style="padding:9px 12px;text-align:center;color:#e2e8f0">{{ $l->days }}</td>
        <td style="padding:9px 12px;color:#94a3b8;font-size:11px">{{ Str::limit($l->reason,22) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['pending'=>'#451a03','approved'=>'#14532d','rejected'=>'#7f1d1d'][$l->status]??'#1e2130' }};color:{{ ['pending'=>'#fb923c','approved'=>'#4ade80','rejected'=>'#fca5a5'][$l->status]??'#94a3b8' }}">{{ ucfirst($l->status) }}</span></td>
        <td style="padding:9px 12px">
            @if($l->status === 'pending')
            <div style="display:flex;gap:3px">
                <form method="POST" action="{{ route('hrm.leaves.approve',$l->id) }}">@csrf @method('PATCH')<button type="submit" style="height:26px;padding:0 8px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:5px;font-size:11px;cursor:pointer">Approve</button></form>
                <form method="POST" action="{{ route('hrm.leaves.reject',$l->id) }}">@csrf @method('PATCH')<button type="submit" style="height:26px;padding:0 8px;background:#7f1d1d;color:#fca5a5;border:.5px solid #991b1b;border-radius:5px;font-size:11px;cursor:pointer">Reject</button></form>
            </div>
            @else
            <span style="color:#4a5568;font-size:11px">—</span>
            @endif
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:32px;text-align:center;color:#4a5568">No leave requests</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $leaves->links() }}</div>
</div>
@endsection
