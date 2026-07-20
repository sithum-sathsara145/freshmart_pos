{{-- expenses/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Expense')
@section('page-title','Edit Expense')
@section('content')
@php $inp = 'width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none;box-sizing:border-box'; @endphp
<div style="padding:14px 16px;max-width:480px">
<form method="POST" action="{{ route('expenses.update',$expense) }}">
@csrf
@method('PUT')
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Expense details</div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Category *</label>
        <select name="expense_category_id" required style="{{ $inp }}">
            @foreach($categories as $c)
            <option value="{{ $c->id }}" {{ old('expense_category_id',$expense->expense_category_id)==$c->id?'selected':'' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Description *</label>
        <input type="text" name="description" value="{{ old('description',$expense->description) }}" required style="{{ $inp }}">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Amount (Rs.) *</label>
            <input type="number" name="amount" value="{{ old('amount',$expense->amount) }}" min="0.01" step="0.01" required style="{{ $inp }}">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Date *</label>
            <input type="date" name="expense_date" value="{{ old('expense_date',\Carbon\Carbon::parse($expense->expense_date)->toDateString()) }}" required style="{{ $inp }}">
        </div>
    </div>
    @if($expense->account_id)
    <div style="font-size:11px;color:var(--text-3)">Paid from <b style="color:var(--text-2)">{{ $expense->account?->name }}</b> — changing the amount adjusts that account by the difference.</div>
    @endif
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('expenses.index') }}" style="height:36px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Changes</button>
</div>
</form>
</div>
@endsection
