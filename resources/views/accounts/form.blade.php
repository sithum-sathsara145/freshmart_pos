{{-- accounts/form.blade.php — add or edit a cash book / bank account --}}
@extends('layouts.app')
@section('title', $account->exists ? 'Edit Account' : 'New Account')
@section('page-title', $account->exists ? 'Edit Account' : 'New Account')
@section('content')
@php $inp = 'width:100%;height:36px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:0 10px;outline:none'; @endphp
<div style="padding:14px 16px;max-width:640px" x-data="{ type: '{{ old('type', $account->type ?? 'cash') }}' }">

<form method="POST" action="{{ $account->exists ? route('accounts.update', $account) : route('accounts.store') }}">
@csrf
@if($account->exists) @method('PUT') @endif

{{-- What kind of account --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Account type</div>
    <div style="display:flex;gap:8px;margin-bottom:14px">
        @foreach([['cash','Cash book','ti-cash'],['bank','Bank account','ti-building-bank']] as [$v,$label,$icon])
        <label style="flex:1;display:flex;align-items:center;gap:8px;padding:11px 13px;border-radius:7px;cursor:pointer;font-size:12px;border:.5px solid var(--border)"
               :style="type === '{{ $v }}' ? 'background:var(--primary-soft);border-color:var(--primary-border);color:var(--primary-text)' : 'background:var(--bg);color:var(--text-2)'">
            <input type="radio" name="type" value="{{ $v }}" x-model="type" style="accent-color:var(--primary)">
            <i class="ti {{ $icon }}" style="font-size:15px"></i>{{ $label }}
        </label>
        @endforeach
    </div>

    <div style="margin-bottom:12px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Name</label>
        <input name="name" required value="{{ old('name', $account->name) }}" style="{{ $inp }}"
               :placeholder="type === 'cash' ? 'e.g. Counter Cash — Colombo' : 'e.g. Sampath — Current'">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Branch</label>
            <select name="branch_id" style="{{ $inp }}">
                <option value="">Not tied to a branch (whole business)</option>
                @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(old('branch_id', $account->branch_id) == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Status</label>
            <select name="status" style="{{ $inp }}">
                <option value="active" @selected(old('status', $account->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $account->status) === 'inactive')>Inactive — hidden from new entries</option>
            </select>
        </div>
    </div>
</div>

{{-- Bank details --}}
<div x-show="type === 'bank'" x-cloak style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Bank details</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Bank name</label>
            <input name="bank_name" value="{{ old('bank_name', $account->bank_name) }}" placeholder="e.g. Sampath Bank" style="{{ $inp }}">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Bank branch</label>
            <input name="bank_branch" value="{{ old('bank_branch', $account->bank_branch) }}" placeholder="e.g. Nugegoda" style="{{ $inp }}">
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Account number</label>
            <input name="account_number" value="{{ old('account_number', $account->account_number) }}" style="{{ $inp }}">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Account type</label>
            <select name="subtype" style="{{ $inp }}">
                <option value="">Choose…</option>
                <option value="savings" @selected(old('subtype', $account->subtype) === 'savings')>Savings</option>
                <option value="current" @selected(old('subtype', $account->subtype) === 'current')>Current</option>
            </select>
        </div>
    </div>
</div>

{{-- Opening + notes --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    @if(! $account->exists)
    <div style="margin-bottom:12px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Opening balance</label>
        <input type="number" name="opening_balance" step="0.01" value="{{ old('opening_balance', 0) }}" style="{{ $inp }}">
        <div style="font-size:10px;color:var(--text-3);margin-top:5px">
            What the account already holds. It goes on the statement as the first line.
        </div>
    </div>
    @else
    <div style="margin-bottom:12px;font-size:11px;color:var(--text-3)">
        Opening balance was <b style="color:var(--text-2)">Rs. {{ number_format((float) $account->opening_balance, 2) }}</b>.
        It can't be changed here — it is already a line on the statement. Record a deposit or withdrawal instead.
    </div>
    @endif

    <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text);cursor:pointer;margin-bottom:12px" x-show="type === 'cash'" x-cloak>
        <input type="checkbox" name="is_cashier_book" value="1" @checked(old('is_cashier_book', $account->is_cashier_book)) style="accent-color:var(--primary);width:15px;height:15px">
        Cashiers hand their takings into this book at the end of a shift
    </label>

    <div>
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Notes (optional)</label>
        <input name="notes" value="{{ old('notes', $account->notes) }}" style="{{ $inp }}">
    </div>
</div>

<div style="display:flex;gap:8px">
    <button type="submit" style="height:36px;padding:0 20px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">
        {{ $account->exists ? 'Save changes' : 'Create account' }}
    </button>
    <a href="{{ route('accounts.index') }}" style="height:36px;padding:0 16px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
</div>
</form>

</div>
@endsection
