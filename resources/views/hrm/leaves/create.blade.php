{{-- hrm/leaves/create.blade.php --}}
@extends('layouts.app')
@section('title','New Leave Request')
@section('page-title','New Leave Request')
@section('content')
@php $inp = 'width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none;box-sizing:border-box'; @endphp
<div style="padding:14px 16px;max-width:480px">
<form method="POST" action="{{ route('hrm.leaves.store') }}">
@csrf
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Leave details</div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Staff member *</label>
        <select name="staff_id" required style="{{ $inp }}">
            <option value="">— Select staff —</option>
            @foreach($staff as $s)
            <option value="{{ $s->id }}" {{ old('staff_id')==$s->id?'selected':'' }}>{{ $s->name }} ({{ $s->role }})</option>
            @endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Leave type *</label>
        <select name="type" required style="{{ $inp }}">
            @foreach(['annual'=>'Annual','sick'=>'Sick','casual'=>'Casual','other'=>'Other'] as $v=>$l)
            <option value="{{ $v }}" {{ old('type','casual')===$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">From *</label>
            <input type="date" name="from_date" value="{{ old('from_date',today()->toDateString()) }}" required style="{{ $inp }}">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">To *</label>
            <input type="date" name="to_date" value="{{ old('to_date',today()->toDateString()) }}" required style="{{ $inp }}">
        </div>
    </div>
    <div>
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Reason</label>
        <input type="text" name="reason" value="{{ old('reason') }}" placeholder="optional" style="{{ $inp }}">
    </div>
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('hrm.leaves.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Submit Request</button>
</div>
</form>
</div>
@endsection
