{{-- customers/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Customer')
@section('page-title','Edit Customer')
@section('content')
<div style="padding:14px 16px;max-width:500px">
<form method="POST" action="{{ route('customers.update',$customer) }}">
@csrf @method('PUT')
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px">
    @foreach([['name','Full name','text',true],['phone','Phone number','text',false],['email','Email address','email',false],['address','Address','text',false],['nic','NIC (national ID)','text',false]] as [$n,$l,$t,$req])
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">{{ $l }}{{ $req?' *':'' }}</label>
        <input type="{{ $t }}" name="{{ $n }}" value="{{ old($n,$customer->$n) }}" {{ $req?'required':'' }}
            style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    </div>
    @endforeach
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-top:12px">
    <div style="font-size:12px;color:var(--text);font-weight:600;margin-bottom:10px"><i class="ti ti-credit-card" style="color:var(--primary);margin-right:4px"></i>Credit sales</div>
    <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text);margin-bottom:12px;cursor:pointer">
        <input type="checkbox" name="credit_approved" value="1" {{ old('credit_approved',$customer->credit_approved) ? 'checked' : '' }} style="width:16px;height:16px;accent-color:var(--success)">
        Approved to buy on credit
    </label>
    <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Credit limit (Rs.) — leave blank for no limit</label>
    <input type="number" min="0" step="0.01" name="credit_limit" value="{{ old('credit_limit',$customer->credit_limit) }}" placeholder="No limit"
        style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    @if($customer->outstandingBalance() > 0)
    <div style="font-size:11px;color:var(--warning-2);margin-top:10px">Current outstanding: <b>Rs. {{ number_format($customer->outstandingBalance(),2) }}</b></div>
    @endif
</div>
<div style="display:flex;gap:8px;margin-top:12px">
    <a href="{{ route('customers.show',$customer) }}" style="height:36px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Update Customer</button>
</div>
</form>
</div>
@endsection
