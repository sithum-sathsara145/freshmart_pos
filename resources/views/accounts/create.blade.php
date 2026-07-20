{{-- accounts/create.blade.php --}}
@extends('layouts.app')
@section('title','Add Account')
@section('page-title','Add Cash / Bank Account')
@section('content')
@php $inp = 'width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none;box-sizing:border-box'; @endphp
<div style="padding:14px 16px;max-width:480px">
<form method="POST" action="{{ route('accounts.store') }}">
@csrf
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Account details</div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Account name *</label>
        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Till cash, BOC current" style="{{ $inp }}">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Type *</label>
            <select name="type" required style="{{ $inp }}">
                <option value="cash" {{ old('type')==='cash'?'selected':'' }}>Cash</option>
                <option value="bank" {{ old('type')==='bank'?'selected':'' }}>Bank</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Account number</label>
            <input type="text" name="account_number" value="{{ old('account_number') }}" placeholder="optional" style="{{ $inp }}">
        </div>
    </div>
    <div style="font-size:11px;color:var(--text-3)">New accounts start at Rs. 0 — move money in with an account transfer.</div>
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('accounts.index') }}" style="height:36px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Account</button>
</div>
</form>
</div>
@endsection
