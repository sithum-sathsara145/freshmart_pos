{{-- suppliers/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Supplier')
@section('page-title','Edit Supplier — '.$supplier->name)
@section('content')
@php $inp = 'width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none;box-sizing:border-box'; @endphp
<div style="padding:14px 16px;max-width:560px">
<form method="POST" action="{{ route('suppliers.update',$supplier) }}">
@csrf
@method('PUT')
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Supplier information</div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Supplier name *</label>
        <input type="text" name="name" value="{{ old('name',$supplier->name) }}" required style="{{ $inp }}">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Contact person</label>
            <input type="text" name="contact_person" value="{{ old('contact_person',$supplier->contact_person) }}" style="{{ $inp }}">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Phone</label>
            <input type="text" name="phone" value="{{ old('phone',$supplier->phone) }}" style="{{ $inp }}">
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Email</label>
            <input type="email" name="email" value="{{ old('email',$supplier->email) }}" style="{{ $inp }}">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">City</label>
            <input type="text" name="city" value="{{ old('city',$supplier->city) }}" style="{{ $inp }}">
        </div>
    </div>
    <div>
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Address</label>
        <input type="text" name="address" value="{{ old('address',$supplier->address) }}" style="{{ $inp }}">
    </div>
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('suppliers.show',$supplier) }}" style="height:36px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Changes</button>
</div>
</form>
</div>
@endsection
