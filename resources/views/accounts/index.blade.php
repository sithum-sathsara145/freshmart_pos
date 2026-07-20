{{-- accounts/index.blade.php --}}
@extends('layouts.app')
@section('title','Cash & Bank')
@section('page-title','Cash & Bank')
@section('content')
<div style="padding:14px 16px">
<div style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:center">
    <div style="font-size:14px;font-weight:500;color:var(--success)">Total balance: Rs. {{ number_format($totalBalance) }}</div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('accounts.transfer') }}" onclick="event.preventDefault();document.getElementById('transfer-modal').style.display='flex'" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrows-exchange" style="font-size:12px"></i>Transfer</a>
        <a href="{{ route('accounts.create') }}" style="height:32px;padding:0 12px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-plus" style="font-size:12px"></i>Add account</a>
    </div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-bottom:14px">
@foreach($accounts as $acc)
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
        <div style="width:36px;height:36px;background:{{ $acc->type==='bank'?'var(--primary-soft)':'var(--success-soft)' }};border-radius:8px;display:flex;align-items:center;justify-content:center">
            <i class="ti {{ $acc->type==='bank'?'ti-building-bank':'ti-cash' }}" style="color:{{ $acc->type==='bank'?'var(--primary-text)':'var(--success)' }};font-size:18px"></i>
        </div>
        <div>
            <div style="font-size:12px;font-weight:500;color:var(--text)">{{ $acc->name }}</div>
            <div style="font-size:10px;color:var(--text-3)">{{ ucfirst($acc->type) }} {{ $acc->account_number ? '· '.$acc->account_number : '' }}</div>
        </div>
    </div>
    <div style="font-size:22px;font-weight:500;color:var(--success)">Rs. {{ number_format($acc->balance) }}</div>
    <a href="{{ route('accounts.transactions',$acc->id) }}" style="display:inline-block;margin-top:8px;font-size:11px;color:var(--primary);text-decoration:none">View transactions →</a>
</div>
@endforeach
</div>

{{-- Transfer modal --}}
<div id="transfer-modal" style="display:none;position:fixed;inset:0;background:var(--overlay);z-index:50;align-items:center;justify-content:center">
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:10px;padding:20px;width:380px">
    <div style="font-size:13px;font-weight:500;color:var(--text);margin-bottom:14px">Fund Transfer</div>
    <form method="POST" action="{{ route('accounts.transfer') }}">
    @csrf
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">From account</label>
        <select name="from_account_id" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }} — Rs. {{ number_format($a->balance) }}</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">To account</label>
        <select name="to_account_id" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Amount (Rs.)</label>
        <input type="number" name="amount" min="1" step="0.01" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="margin-bottom:14px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Note</label>
        <input type="text" name="notes" placeholder="Optional" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="display:flex;gap:8px">
        <button type="button" onclick="document.getElementById('transfer-modal').style.display='none'" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
        <button type="submit" style="flex:1;height:36px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">Transfer Now</button>
    </div>
    </form>
</div>
</div>
</div>
@endsection
