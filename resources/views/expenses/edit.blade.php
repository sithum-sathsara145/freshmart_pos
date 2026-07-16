{{-- expenses/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Expense')
@section('page-title','Edit Expense')
@section('content')
@php $inp = 'width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none;box-sizing:border-box'; @endphp
<div style="padding:14px 16px;max-width:480px">
<form method="POST" action="{{ route('expenses.update',$expense) }}">
@csrf
@method('PUT')
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Expense details</div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Category *</label>
        <select name="expense_category_id" required style="{{ $inp }}">
            @foreach($categories as $c)
            <option value="{{ $c->id }}" {{ old('expense_category_id',$expense->expense_category_id)==$c->id?'selected':'' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Description *</label>
        <input type="text" name="description" value="{{ old('description',$expense->description) }}" required style="{{ $inp }}">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Amount (Rs.) *</label>
            <input type="number" name="amount" value="{{ old('amount',$expense->amount) }}" min="0.01" step="0.01" required style="{{ $inp }}">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Date *</label>
            <input type="date" name="expense_date" value="{{ old('expense_date',\Carbon\Carbon::parse($expense->expense_date)->toDateString()) }}" required style="{{ $inp }}">
        </div>
    </div>
    @if($expense->account_id)
    <div style="font-size:11px;color:#64748b">Paid from <b style="color:#94a3b8">{{ $expense->account?->name }}</b> — changing the amount adjusts that account by the difference.</div>
    @endif
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('expenses.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Changes</button>
</div>
</form>
</div>
@endsection
