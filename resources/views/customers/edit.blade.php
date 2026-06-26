{{-- customers/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Customer')
@section('page-title','Edit Customer')
@section('content')
<div style="padding:14px 16px;max-width:500px">
<form method="POST" action="{{ route('customers.update',$customer) }}">
@csrf @method('PUT')
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px">
    @foreach([['name','Full name','text',true],['phone','Phone number','text',false],['email','Email address','email',false],['address','Address','text',false]] as [$n,$l,$t,$req])
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">{{ $l }}{{ $req?' *':'' }}</label>
        <input type="{{ $t }}" name="{{ $n }}" value="{{ old($n,$customer->$n) }}" {{ $req?'required':'' }}
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    @endforeach
</div>
<div style="display:flex;gap:8px;margin-top:12px">
    <a href="{{ route('customers.show',$customer) }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Update Customer</button>
</div>
</form>
</div>
@endsection
