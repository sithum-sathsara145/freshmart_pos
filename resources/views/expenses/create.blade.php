{{-- expenses/create.blade.php --}}
@extends('layouts.app')
@section('title','Add Expense')
@section('page-title','Add Expense')
@section('content')
<div style="padding:14px 16px;max-width:520px">
<form method="POST" action="{{ route('expenses.store') }}">
@csrf
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px">
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Category *</label>
        <select name="expense_category_id" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="">— Select category —</option>
            @foreach($categories as $c)<option value="{{ $c->id }}" {{ old('expense_category_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Description *</label>
        <input type="text" name="description" value="{{ old('description') }}" required placeholder="e.g. Monthly electricity bill"
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Amount (Rs.) *</label>
            <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required
                style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Date *</label>
            <input type="date" name="expense_date" value="{{ old('expense_date', today()->toDateString()) }}" required
                style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
        </div>
    </div>
    <div>
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Pay from account</label>
        <select name="account_id" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="">— None —</option>
            @foreach($accounts as $a)<option value="{{ $a->id }}" {{ old('account_id')==$a->id?'selected':'' }}>{{ $a->name }} (Rs. {{ number_format($a->balance) }})</option>@endforeach
        </select>
    </div>
</div>
<div style="display:flex;gap:8px;margin-top:12px">
    <a href="{{ route('expenses.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Expense</button>
</div>
</form>
</div>
@endsection
