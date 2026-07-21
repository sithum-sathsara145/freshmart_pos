{{-- reports/gross_profit.blade.php — profit grouped by item, category, supplier, location, invoice or customer --}}
@extends('layouts.app')
@section('title','Gross Profit')
@section('page-title','Reports — Gross Profit')
@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 2);
    $trim  = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
    $modes = [
        'item' => 'By item', 'category' => 'By category', 'supplier' => 'By supplier',
        'location' => 'By location', 'invoice' => 'By invoice', 'customer' => 'By customer',
    ];
    $heading = ['item'=>'Item','category'=>'Category','supplier'=>'Supplier','location'=>'Branch','invoice'=>'Invoice','customer'=>'Customer'][$by];
@endphp
<div style="padding:14px 16px">

@include('reports.partials.header', [
    'title'  => 'Gross profit',
    'icon'   => 'ti-trending-up',
    'export' => 'gross_profit_' . $by,
])

<form method="GET" style="margin-bottom:14px">
    @foreach(request()->except(['by','page']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    <div style="display:inline-flex;flex-wrap:wrap;background:var(--surface);border:.5px solid var(--border);border-radius:7px;padding:2px">
        @foreach($modes as $key => $label)
        <button type="submit" name="by" value="{{ $key }}"
                style="height:26px;padding:0 11px;border:none;border-radius:5px;font-size:11.5px;cursor:pointer;
                       background:{{ $by === $key ? 'var(--primary-soft)' : 'transparent' }};
                       color:{{ $by === $key ? 'var(--primary-text)' : 'var(--text-3)' }}">{{ $label }}</button>
        @endforeach
    </div>
</form>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    @foreach([
        ['Revenue', 'Rs. '.number_format($totals['revenue']), 'var(--text)'],
        ['Cost of goods', 'Rs. '.number_format($totals['cost']), 'var(--text-2)'],
        ['Gross profit', 'Rs. '.number_format($totals['profit']), 'var(--success)'],
        ['Margin', $totals['margin'].'%', 'var(--primary-text)'],
    ] as [$l,$v,$c])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:17px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach([$heading, in_array($by,['item','category','supplier']) ? 'Qty' : 'Lines', 'Revenue', 'Cost', 'Profit', 'Margin'] as $i => $h)
        <th style="padding:9px 12px;text-align:{{ $i === 0 ? 'left' : 'right' }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $r)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--text);font-weight:500">
            @if($by === 'invoice' && $r['sale_id'])
            <a href="{{ route('sales.show', $r['sale_id']) }}" style="color:var(--primary-text);text-decoration:none">{{ $r['label'] }}</a>
            @else
            {{ $r['label'] }}
            @endif
        </td>
        <td style="padding:8px 12px;text-align:right;color:var(--text-2)">
            {{ in_array($by,['item','category','supplier']) ? $trim($r['qty']).' '.($by === 'item' ? $r['unit'] : '') : $r['lines'] }}
        </td>
        <td style="padding:8px 12px;text-align:right;color:var(--text)">{{ $money($r['revenue']) }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--text-3)">{{ $money($r['cost']) }}</td>
        <td style="padding:8px 12px;text-align:right;font-weight:500;color:{{ $r['profit'] < 0 ? 'var(--danger)' : 'var(--success)' }}">{{ $money($r['profit']) }}</td>
        <td style="padding:8px 12px;text-align:right;color:{{ $r['margin'] < 0 ? 'var(--danger)' : 'var(--text-2)' }}">{{ $r['margin'] }}%</td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:28px;text-align:center;color:var(--text-4)">Nothing sold in this period.</td></tr>
    @endforelse
    </tbody>
    @if($rows->count())
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td style="padding:9px 12px;color:var(--text-2);font-weight:500">Total ({{ $rows->count() }})</td>
        <td></td>
        <td style="padding:9px 12px;text-align:right;color:var(--text);font-weight:600">{{ $money($totals['revenue']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500">{{ $money($totals['cost']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--success);font-weight:600">{{ $money($totals['profit']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-2);font-weight:500">{{ $totals['margin'] }}%</td>
    </tr></tfoot>
    @endif
</table>
</div>

<div style="font-size:10.5px;color:var(--text-4);margin-top:10px;line-height:1.5">
    Profit is measured against the cost recorded on each sale line at the time it sold.
    @if($netsReturns)
    Returns are netted off both revenue and cost, so refunded stock stops counting as profit.
    @else
    Grouped this way the figures are gross — returns are known per item, not per {{ strtolower($heading) }}.
    @endif
    @if($by === 'supplier')
    Supplier is taken from the most recent purchase of each item, since products don't carry one of their own.
    @endif
</div>
</div>
@endsection
