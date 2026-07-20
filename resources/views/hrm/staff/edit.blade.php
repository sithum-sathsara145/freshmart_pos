{{-- hrm/staff/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Staff')
@section('page-title','Edit Staff — '.$staff->name)
@section('content')
@php $inp = 'width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none;box-sizing:border-box'; @endphp
<div style="padding:14px 16px;max-width:560px">
<form method="POST" action="{{ route('hrm.staff.update',$staff) }}">
@csrf
@method('PUT')
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Staff information</div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Full name *</label>
        <input type="text" name="name" value="{{ old('name',$staff->name) }}" required style="{{ $inp }}">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Phone</label>
            <input type="text" name="phone" value="{{ old('phone',$staff->phone) }}" style="{{ $inp }}">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Email</label>
            <input type="email" name="email" value="{{ old('email',$staff->email) }}" style="{{ $inp }}">
        </div>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Address</label>
        <input type="text" name="address" value="{{ old('address',$staff->address) }}" style="{{ $inp }}">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Job title *</label>
            <select name="role" required style="{{ $inp }}">
                @foreach($jobTitles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $staff->role) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Basic salary (Rs.) *</label>
            <input type="number" name="basic_salary" value="{{ old('basic_salary',$staff->basic_salary) }}" min="0" step="0.01" required style="{{ $inp }}">
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Joined</label>
            <div style="{{ $inp }};color:var(--text-3)">{{ $staff->join_date ? \Carbon\Carbon::parse($staff->join_date)->format('d M Y') : '—' }}</div>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Status *</label>
            <select name="status" required style="{{ $inp }}">
                <option value="active" {{ old('status',$staff->status)==='active'?'selected':'' }}>Active</option>
                <option value="inactive" {{ old('status',$staff->status)==='inactive'?'selected':'' }}>Inactive</option>
            </select>
        </div>
    </div>
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:4px">Login account</div>
    <div style="font-size:11px;color:var(--text-3);margin-bottom:10px;line-height:1.5">
        Linking a login lets their POS counter sessions record attendance automatically.
        Unlinking leaves the attendance and payroll history untouched.
    </div>
    <select name="user_id" style="{{ $inp }}">
        <option value="">— no login account —</option>
        @foreach($users as $u)
        <option value="{{ $u->id }}" @selected(old('user_id', $staff->user_id) == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
        @endforeach
    </select>
    @error('user_id')<div style="font-size:11px;color:var(--danger-text);margin-top:5px">{{ $message }}</div>@enderror
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('hrm.staff.show',$staff) }}" style="height:36px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Changes</button>
</div>
</form>
</div>
@endsection
