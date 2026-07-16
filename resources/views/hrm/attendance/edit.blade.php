{{-- hrm/attendance/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Attendance')
@section('page-title','Edit Attendance — '.($attendance->staff?->name ?? ''))
@section('content')
@php $inp = 'width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none;box-sizing:border-box'; @endphp
<div style="padding:14px 16px;max-width:420px">
<form method="POST" action="{{ route('hrm.attendance.update',$attendance) }}">
@csrf
@method('PUT')
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">
        {{ $attendance->staff?->name }} — {{ \Carbon\Carbon::parse($attendance->date)->format('d M Y') }}
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Time in</label>
            <input type="time" name="time_in" value="{{ old('time_in',$attendance->time_in) }}" style="{{ $inp }}">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Time out</label>
            <input type="time" name="time_out" value="{{ old('time_out',$attendance->time_out) }}" style="{{ $inp }}">
        </div>
    </div>
    <div>
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Status *</label>
        <select name="status" required style="{{ $inp }}">
            @foreach(['present'=>'Present','absent'=>'Absent','leave'=>'Leave','half_day'=>'Half day'] as $v=>$l)
            <option value="{{ $v }}" {{ old('status',$attendance->status)===$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('hrm.attendance.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Changes</button>
</div>
</form>
</div>
@endsection
