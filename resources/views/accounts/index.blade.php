{{-- accounts/index.blade.php — cash books and bank accounts --}}
@extends('layouts.app')
@section('title','Cash & Bank')
@section('page-title','Cash & Bank')
@section('content')
@php
$card = 'background:var(--surface);border:.5px solid var(--border);border-radius:8px';
$inp  = 'width:100%;height:36px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:0 10px;outline:none';
@endphp
<div style="padding:14px 16px" x-data="{ transfer: false }">

{{-- Totals --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-bottom:12px">
    @foreach([['Cash in hand', $cashTotal, 'var(--success)', 'ti-cash'],
              ['In the bank', $bankTotal, 'var(--info)', 'ti-building-bank'],
              ['Total', $totalBalance, 'var(--primary-text)', 'ti-wallet']] as [$label, $value, $colour, $icon])
    <div style="{{ $card }};padding:12px 14px">
        <div style="font-size:10px;color:var(--text-3);display:flex;align-items:center;gap:5px">
            <i class="ti {{ $icon }}" style="font-size:13px"></i>{{ $label }}
        </div>
        <div style="font-size:21px;font-weight:600;color:{{ $colour }};margin-top:4px">Rs. {{ number_format($value, 2) }}</div>
    </div>
    @endforeach
</div>

<div style="display:flex;gap:8px;margin-bottom:12px">
    @can('accounts.transfer')
    <button type="button" @click="transfer = true"
            style="height:34px;padding:0 12px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px">
        <i class="ti ti-arrows-exchange" style="font-size:13px"></i>Transfer money
    </button>
    @endcan
    @can('accounts.manage')
    <a href="{{ route('accounts.create') }}" style="height:34px;padding:0 14px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>New account
    </a>
    @endcan
</div>

{{-- Each group --}}
@foreach([['Cash books', $cashBooks, 'ti-cash'], ['Bank accounts', $bankAccounts, 'ti-building-bank']] as [$heading, $list, $icon])
<div style="{{ $card }};overflow:hidden;margin-bottom:12px">
    <div style="padding:10px 14px;border-bottom:.5px solid var(--border);font-size:12px;font-weight:500;color:var(--text-2);display:flex;align-items:center;gap:6px">
        <i class="ti {{ $icon }}" style="font-size:14px"></i>{{ $heading }}
        <span style="color:var(--text-3);font-weight:400">({{ $list->count() }})</span>
    </div>

    @if($list->isEmpty())
    <div style="padding:18px 14px;font-size:12px;color:var(--text-3)">
        No {{ strtolower($heading) }} yet.
        @can('accounts.manage')<a href="{{ route('accounts.create') }}" style="color:var(--primary-text);text-decoration:none">Add one</a>.@endcan
    </div>
    @else
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            @foreach(['Account','Details','Branch','Balance',''] as $h)
            <th style="padding:8px 12px;text-align:{{ $h === 'Balance' ? 'right' : 'left' }};color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @foreach($list as $a)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:9px 12px">
                <a href="{{ route('accounts.transactions', $a->id) }}" style="color:var(--text);font-weight:500;text-decoration:none">{{ $a->name }}</a>
                @if($a->status !== 'active')
                <span style="font-size:10px;padding:1px 6px;border-radius:8px;background:var(--surface-2);color:var(--text-3);margin-left:5px">Inactive</span>
                @endif
                @if($a->is_cashier_book)
                <span style="font-size:10px;padding:1px 6px;border-radius:8px;background:var(--info-soft);color:var(--info);margin-left:5px">Cashier hand-in</span>
                @endif
            </td>
            <td style="padding:9px 12px;color:var(--text-3)">
                @if($a->type === 'bank')
                    {{ collect([$a->bank_name, $a->bank_branch, $a->subtype ? ucfirst($a->subtype) : null, $a->account_number])->filter()->implode(' · ') ?: '—' }}
                @else
                    {{ $a->notes ?: '—' }}
                @endif
            </td>
            <td style="padding:9px 12px;color:var(--text-3)">{{ $a->branch?->name ?? 'Whole business' }}</td>
            <td style="padding:9px 12px;text-align:right;font-weight:600;color:{{ $a->balance < 0 ? 'var(--danger)' : 'var(--text)' }}">
                Rs. {{ number_format((float) $a->balance, 2) }}
            </td>
            <td style="padding:9px 12px;text-align:right;white-space:nowrap">
                <a href="{{ route('accounts.transactions', $a->id) }}" title="Statement"
                   style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:inline-flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-list" style="font-size:12px"></i></a>
                @can('accounts.manage')
                <a href="{{ route('accounts.edit', $a) }}" title="Edit"
                   style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:inline-flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none;margin-left:3px"><i class="ti ti-edit" style="font-size:12px"></i></a>
                @endcan
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
@endforeach

{{-- Transfer --}}
@can('accounts.transfer')
<template x-teleport="body">
<div x-show="transfer" x-cloak @keydown.escape.window="transfer = false" @click.self="transfer = false"
     style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:50">
    <div style="{{ $card }};padding:18px;width:400px">
        <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:6px">
            <i class="ti ti-arrows-exchange" style="color:var(--primary)"></i>Transfer money
        </div>
        <form method="POST" action="{{ route('accounts.transfer') }}">
            @csrf
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">From</label>
            <select name="from_account_id" required style="{{ $inp }};margin-bottom:10px">
                @foreach($transferable as $a)
                <option value="{{ $a->id }}">{{ $a->describe() }} — Rs. {{ number_format((float) $a->balance, 2) }}</option>
                @endforeach
            </select>

            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">To</label>
            <select name="to_account_id" required style="{{ $inp }};margin-bottom:10px">
                @foreach($transferable as $a)
                <option value="{{ $a->id }}">{{ $a->describe() }}</option>
                @endforeach
            </select>

            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Amount</label>
            <input type="number" name="amount" step="0.01" min="0.01" required style="{{ $inp }};margin-bottom:10px">

            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Note (optional)</label>
            <input name="notes" placeholder="e.g. Banked the day's takings" style="{{ $inp }};margin-bottom:14px">

            <div style="display:flex;gap:8px">
                <button type="button" @click="transfer = false" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
                <button type="submit" style="flex:1;height:36px;background:var(--primary-soft);border:.5px solid var(--primary-border);border-radius:6px;color:var(--primary-text);font-size:12px;font-weight:600;cursor:pointer">Transfer</button>
            </div>
        </form>
    </div>
</div>
</template>
@endcan

</div>
@endsection
