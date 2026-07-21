{{-- reports/cashiers.blade.php — what each cashier took, by cashier / customer / item --}}
@extends('layouts.app')
@section('title','Cashier Report')
@section('page-title','Reports — Cashier-wise')
@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 2);
    $trim  = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
    $sel   = 'height:32px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none';
    $modes = ['cashier' => 'Sales summary', 'customer' => 'By customer', 'item' => 'By item'];
@endphp
<div style="padding:14px 16px">

@include('reports.partials.header', [
    'title'  => 'Cashier-wise',
    'icon'   => 'ti-user-dollar',
    'export' => 'cashiers_' . $by,
])

<form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px">
    @foreach(request()->except(['by','user_id','counter_id','page']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach

    <div style="display:flex;background:var(--surface);border:.5px solid var(--border);border-radius:7px;padding:2px">
        @foreach($modes as $key => $label)
        <button type="submit" name="by" value="{{ $key }}"
                style="height:26px;padding:0 11px;border:none;border-radius:5px;font-size:11.5px;cursor:pointer;
                       background:{{ $by === $key ? 'var(--primary-soft)' : 'transparent' }};
                       color:{{ $by === $key ? 'var(--primary-text)' : 'var(--text-3)' }}">{{ $label }}</button>
        @endforeach
    </div>

    <select name="user_id" onchange="this.form.submit()" style="{{ $sel }}">
        <option value="">All cashiers</option>
        @foreach($cashiers as $c)
        <option value="{{ $c->id }}" @selected($filters['user_id'] == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
    <select name="counter_id" onchange="this.form.submit()" style="{{ $sel }}">
        <option value="">All counters</option>
        @foreach($counters as $c)
        <option value="{{ $c->id }}" @selected($filters['counter_id'] == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
    <input type="hidden" name="by" value="{{ $by }}">
</form>

<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:14px">
    @foreach([
        ['Invoices', number_format($totals['invoices']), 'var(--text)'],
        ['Net sales', 'Rs. '.number_format($totals['net']), 'var(--success)'],
        ['Cash', 'Rs. '.number_format($totals['cash']), 'var(--text-2)'],
        ['Card', 'Rs. '.number_format($totals['card']), 'var(--primary-text)'],
        ['On credit', 'Rs. '.number_format($totals['credit']), 'var(--warning-2)'],
    ] as [$l,$v,$c])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:16px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @php
            $heads = match($by) {
                'item'     => ['Cashier','Item','Qty','Invoices','Net sales','Cost','Profit'],
                'customer' => ['Cashier','Customer','Invoices','Net sales'],
                default    => ['Cashier','Invoices','Net sales','Cash','Card','On credit'],
            };
        @endphp
        @foreach($heads as $i => $h)
        <th style="padding:9px 12px;text-align:{{ $i === 0 || $h === 'Item' || $h === 'Customer' ? 'left' : 'right' }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $r)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--text);font-weight:500;white-space:nowrap">{{ $r['cashier'] }}</td>
        @if($by === 'item')
            <td style="padding:8px 12px;color:var(--text-2)">{{ $r['label'] }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--text-2)">{{ $trim($r['qty']) }} {{ $r['unit'] }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--text-3)">{{ $r['invoices'] }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--text);font-weight:500">{{ $money($r['net']) }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--text-3)">{{ $money($r['cost']) }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--success)">{{ $money($r['net'] - $r['cost']) }}</td>
        @elseif($by === 'customer')
            <td style="padding:8px 12px;color:var(--text-2)">{{ $r['label'] }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--text-3)">{{ $r['invoices'] }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--text);font-weight:500">{{ $money($r['net']) }}</td>
        @else
            <td style="padding:8px 12px;text-align:right;color:var(--text-3)">{{ $r['invoices'] }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--text);font-weight:500">{{ $money($r['net']) }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--text-2)">{{ $money($r['cash']) }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--primary-text)">{{ $money($r['card']) }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--warning-2)">{{ $money($r['credit']) }}</td>
        @endif
    </tr>
    @empty
    <tr><td colspan="7" style="padding:28px;text-align:center;color:var(--text-4)">Nothing sold in this period.</td></tr>
    @endforelse
    </tbody>
    @if($rows->count() && $by === 'cashier')
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td style="padding:9px 12px;color:var(--text-2);font-weight:500">Total</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-2);font-weight:500">{{ $totals['invoices'] }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text);font-weight:600">{{ $money($totals['net']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-2);font-weight:500">{{ $money($totals['cash']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--primary-text);font-weight:500">{{ $money($totals['card']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--warning-2);font-weight:500">{{ $money($totals['credit']) }}</td>
    </tr></tfoot>
    @endif
</table>
</div>
</div>
@endsection
