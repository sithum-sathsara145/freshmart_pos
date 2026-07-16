{{-- accounts/create.blade.php --}}
@extends('layouts.app')
@section('title','Add Account')
@section('page-title','Add Cash / Bank Account')
@section('content')
@php $inp = 'width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none;box-sizing:border-box'; @endphp
<div style="padding:14px 16px;max-width:480px">
<form method="POST" action="{{ route('accounts.store') }}">
@csrf
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Account details</div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Account name *</label>
        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Till cash, BOC current" style="{{ $inp }}">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Type *</label>
            <select name="type" required style="{{ $inp }}">
                <option value="cash" {{ old('type')==='cash'?'selected':'' }}>Cash</option>
                <option value="bank" {{ old('type')==='bank'?'selected':'' }}>Bank</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Account number</label>
            <input type="text" name="account_number" value="{{ old('account_number') }}" placeholder="optional" style="{{ $inp }}">
        </div>
    </div>
    <div style="font-size:11px;color:#64748b">New accounts start at Rs. 0 — move money in with an account transfer.</div>
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('accounts.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Account</button>
</div>
</form>
</div>
@endsection
