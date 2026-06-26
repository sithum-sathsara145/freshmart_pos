{{-- accounts/index.blade.php --}}
@extends('layouts.app')
@section('title','Cash & Bank')
@section('page-title','Cash & Bank')
@section('content')
<div style="padding:14px 16px">
<div style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:center">
    <div style="font-size:14px;font-weight:500;color:#4ade80">Total balance: Rs. {{ number_format($totalBalance) }}</div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('accounts.transfer') }}" onclick="event.preventDefault();document.getElementById('transfer-modal').style.display='flex'" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrows-exchange" style="font-size:12px"></i>Transfer</a>
        <a href="{{ route('accounts.create') }}" style="height:32px;padding:0 12px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-plus" style="font-size:12px"></i>Add account</a>
    </div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-bottom:14px">
@foreach($accounts as $acc)
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
        <div style="width:36px;height:36px;background:{{ $acc->type==='bank'?'#312e81':'#14532d' }};border-radius:8px;display:flex;align-items:center;justify-content:center">
            <i class="ti {{ $acc->type==='bank'?'ti-building-bank':'ti-cash' }}" style="color:{{ $acc->type==='bank'?'#a5b4fc':'#4ade80' }};font-size:18px"></i>
        </div>
        <div>
            <div style="font-size:12px;font-weight:500;color:#e2e8f0">{{ $acc->name }}</div>
            <div style="font-size:10px;color:#64748b">{{ ucfirst($acc->type) }} {{ $acc->account_number ? '· '.$acc->account_number : '' }}</div>
        </div>
    </div>
    <div style="font-size:22px;font-weight:500;color:#4ade80">Rs. {{ number_format($acc->balance) }}</div>
    <a href="{{ route('accounts.transactions',$acc->id) }}" style="display:inline-block;margin-top:8px;font-size:11px;color:#818cf8;text-decoration:none">View transactions →</a>
</div>
@endforeach
</div>

{{-- Transfer modal --}}
<div id="transfer-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:50;align-items:center;justify-content:center">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:10px;padding:20px;width:380px">
    <div style="font-size:13px;font-weight:500;color:#e2e8f0;margin-bottom:14px">Fund Transfer</div>
    <form method="POST" action="{{ route('accounts.transfer') }}">
    @csrf
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">From account</label>
        <select name="from_account_id" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }} — Rs. {{ number_format($a->balance) }}</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">To account</label>
        <select name="to_account_id" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Amount (Rs.)</label>
        <input type="number" name="amount" min="1" step="0.01" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="margin-bottom:14px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Note</label>
        <input type="text" name="notes" placeholder="Optional" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="display:flex;gap:8px">
        <button type="button" onclick="document.getElementById('transfer-modal').style.display='none'" style="flex:1;height:36px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">Cancel</button>
        <button type="submit" style="flex:1;height:36px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">Transfer Now</button>
    </div>
    </form>
</div>
</div>
</div>
@endsection
