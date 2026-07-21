{{-- accounts/transactions.blade.php — a bank-style statement for one account --}}
@extends('layouts.app')
@section('title','Statement — '.$account->name)
@section('page-title','Statement — '.$account->name)
@section('content')
@php
$card = 'background:var(--surface);border:.5px solid var(--border);border-radius:8px';
$inp  = 'height:34px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:0 9px;outline:none';
$closing = $opening + $moneyIn - $moneyOut;
@endphp
<div style="padding:14px 16px" x-data="{ entry: false, direction: 'credit' }">

{{-- Header --}}
<div style="{{ $card }};padding:14px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
    <div>
        <div style="font-size:11px;color:var(--text-3)">
            {{ $account->type === 'bank' ? 'Bank account' : 'Cash book' }}
            @if($account->type === 'bank' && ($account->bank_name || $account->account_number))
                · {{ collect([$account->bank_name, $account->bank_branch, $account->subtype ? ucfirst($account->subtype) : null, $account->account_number])->filter()->implode(' · ') }}
            @endif
            · {{ $account->branch?->name ?? 'Whole business' }}
        </div>
        <div style="font-size:24px;font-weight:600;color:{{ $account->balance < 0 ? 'var(--danger)' : 'var(--text)' }};margin-top:4px">
            Rs. {{ number_format((float) $account->balance, 2) }}
        </div>
        @if(abs($derived - (float) $account->balance) > 0.005)
        <div style="font-size:11px;color:var(--danger);margin-top:4px">
            <i class="ti ti-alert-triangle" style="font-size:12px;vertical-align:-1px"></i>
            The entries below add up to Rs. {{ number_format($derived, 2) }} — they disagree with the stored balance.
        </div>
        @endif
    </div>
    <div style="display:flex;gap:8px">
        @can('accounts.entry')
        <button type="button" @click="entry = true; direction = 'credit'"
                style="height:34px;padding:0 12px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px">
            <i class="ti ti-plus" style="font-size:13px"></i>Deposit
        </button>
        <button type="button" @click="entry = true; direction = 'debit'"
                style="height:34px;padding:0 12px;background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px">
            <i class="ti ti-minus" style="font-size:13px"></i>Withdraw
        </button>
        @endcan
        <a href="{{ route('accounts.index') }}" style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
            <i class="ti ti-arrow-left" style="font-size:12px"></i>All accounts
        </a>
    </div>
</div>

{{-- Period --}}
<form method="GET" style="display:flex;gap:8px;align-items:end;margin-bottom:12px;flex-wrap:wrap">
    <div>
        <label style="display:block;font-size:10px;color:var(--text-3);margin-bottom:4px">From</label>
        <input type="date" name="from_date" value="{{ $from }}" style="{{ $inp }}">
    </div>
    <div>
        <label style="display:block;font-size:10px;color:var(--text-3);margin-bottom:4px">To</label>
        <input type="date" name="to_date" value="{{ $to }}" style="{{ $inp }}">
    </div>
    <button type="submit" style="{{ $inp }};padding:0 14px;background:var(--surface-2);color:var(--text-2);cursor:pointer">Show</button>
    @if($from || $to)
    <a href="{{ route('accounts.transactions', $account->id) }}" style="{{ $inp }};padding:0 12px;background:var(--surface-2);color:var(--text-3);display:flex;align-items:center;text-decoration:none">Clear</a>
    @endif
</form>

{{-- Period summary --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:12px">
    @foreach([['Opening', $opening, 'var(--text-2)'], ['Money in', $moneyIn, 'var(--success)'],
              ['Money out', $moneyOut, 'var(--danger)'], ['Closing', $closing, 'var(--text)']] as [$label, $value, $colour])
    <div style="{{ $card }};padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3)">{{ $label }}</div>
        <div style="font-size:17px;font-weight:600;color:{{ $colour }};margin-top:3px">Rs. {{ number_format($value, 2) }}</div>
    </div>
    @endforeach
</div>

{{-- Entries --}}
<div style="{{ $card }};overflow:hidden">
<div style="overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:12px;min-width:660px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Date','Details','Reference','In','Out','Balance'] as $h)
        <th style="padding:8px 12px;text-align:{{ in_array($h, ['In','Out','Balance']) ? 'right' : 'left' }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($entries as $e)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text-3);white-space:nowrap">{{ $e->occurred_at?->format('d M Y') }}</td>
        <td style="padding:9px 12px;color:var(--text)">
            {{ $e->label() }}
            @if($e->counterparty)
            <span style="color:var(--text-3)"> · {{ $e->counterparty->name }}</span>
            @endif
            {{-- For takings banked at a counter close, the notes that actually came
                 in — so the cash can be checked against the ledger entry. --}}
            @php($notes = $e->source_type === 'counter_close' ? ($depositDenoms[$e->source_id] ?? null) : null)
            @if($notes)
            <div style="font-size:10.5px;color:var(--text-3);margin-top:3px">
                @foreach(collect($notes)->sortKeysDesc() as $denom => $qty)
                <span style="white-space:nowrap">{{ $qty }} × {{ number_format((int) $denom) }}</span>{{ ! $loop->last ? ' · ' : '' }}
                @endforeach
            </div>
            @endif
        </td>
        <td style="padding:9px 12px;color:var(--text-3);font-size:11px">{{ $e->reference ?: '—' }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--success)">{{ $e->isCredit() ? 'Rs. '.number_format((float) $e->amount, 2) : '' }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--danger)">{{ $e->isCredit() ? '' : 'Rs. '.number_format((float) $e->amount, 2) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-2);white-space:nowrap">Rs. {{ number_format((float) $e->balance_after, 2) }}</td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:22px 12px;text-align:center;color:var(--text-3)">
        Nothing on this account {{ $from || $to ? 'in that period' : 'yet' }}.
    </td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@if($entries->hasPages())<div style="margin-top:12px">{{ $entries->links() }}</div>@endif

{{-- Deposit / withdrawal --}}
@can('accounts.entry')
<template x-teleport="body">
<div x-show="entry" x-cloak @keydown.escape.window="entry = false" @click.self="entry = false"
     style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:50">
    <div style="{{ $card }};padding:18px;width:380px">
        <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:4px"
             x-text="direction === 'credit' ? 'Record a deposit' : 'Record a withdrawal'"></div>
        <div style="font-size:11px;color:var(--text-3);margin-bottom:14px">
            Into <b style="color:var(--text-2)">{{ $account->name }}</b>. Use this for money that doesn't come from a sale, purchase or expense.
        </div>
        <form method="POST" action="{{ route('accounts.entry', $account) }}">
            @csrf
            <input type="hidden" name="direction" :value="direction">

            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Amount</label>
            <input type="number" name="amount" step="0.01" min="0.01" required style="{{ $inp }};width:100%;height:36px;margin-bottom:10px">

            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Date</label>
            <input type="date" name="occurred_at" value="{{ date('Y-m-d') }}" style="{{ $inp }};width:100%;height:36px;margin-bottom:10px">

            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">What is it for?</label>
            <input name="description" required placeholder="e.g. Owner's cash injection" style="{{ $inp }};width:100%;height:36px;margin-bottom:14px">

            <div style="display:flex;gap:8px">
                <button type="button" @click="entry = false" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
                <button type="submit" style="flex:1;height:36px;border:none;border-radius:6px;color:#fff;font-size:12px;font-weight:600;cursor:pointer"
                        :style="direction === 'credit' ? 'background:var(--success-solid)' : 'background:var(--danger-solid)'"
                        x-text="direction === 'credit' ? 'Record deposit' : 'Record withdrawal'"></button>
            </div>
        </form>
    </div>
</div>
</template>
@endcan

</div>
@endsection
