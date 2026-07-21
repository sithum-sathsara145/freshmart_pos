{{-- reports/refunds.blade.php — what came back, by item --}}
@extends('layouts.app')
@section('title','Refunds & Cancellations')
@section('page-title','Reports — Refunds & Cancellations')
@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 2);
    $trim  = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
    // What came back is stock information, but what it cost and the margin given
    // up are not — those stay behind the same permission as every other margin.
    $showCost = auth()->user()->can('reports.profit');
@endphp
<div style="padding:14px 16px">

@include('reports.partials.header', [
    'title'  => 'Refunds &amp; cancellations',
    'icon'   => 'ti-arrow-back-up',
    'export' => 'refunds',
])

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    @foreach([
        ['Credit notes', number_format($totals['notes']), 'var(--text)'],
        ['Items returned', $trim($totals['qty']), 'var(--text-2)'],
        ['Refunded', 'Rs. '.number_format($totals['revenue']), 'var(--danger)'],
    ] + ($showCost ? [['Margin given back', 'Rs. '.number_format($totals['profit']), 'var(--warning-2)']] : []) as [$l,$v,$c])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:17px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(array_merge(['Item','Qty back','Credit notes','Refunded'], $showCost ? ['Cost back','Margin lost'] : []) as $i => $h)
        <th style="padding:9px 12px;text-align:{{ $i === 0 ? 'left' : 'right' }};color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $r)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--text);font-weight:500">{{ $r['label'] }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--text-2)">{{ $trim($r['qty']) }} {{ $r['unit'] }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--text-3)">{{ $r['notes'] }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--danger);font-weight:500">{{ $money($r['revenue']) }}</td>
        @if($showCost)
        <td style="padding:8px 12px;text-align:right;color:var(--text-3)">{{ $money($r['cost']) }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--warning-2)">{{ $money($r['profit']) }}</td>
        @endif
    </tr>
    @empty
    <tr><td colspan="{{ $showCost ? 6 : 4 }}" style="padding:28px;text-align:center;color:var(--text-4)">Nothing returned in this period.</td></tr>
    @endforelse
    </tbody>
    @if($rows->count())
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td style="padding:9px 12px;color:var(--text-2);font-weight:500">Total</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-2);font-weight:500">{{ $trim($totals['qty']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500">{{ $totals['notes'] }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--danger);font-weight:600">{{ $money($totals['revenue']) }}</td>
        @if($showCost)
        <td style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500">{{ $money($totals['cost']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--warning-2);font-weight:500">{{ $money($totals['profit']) }}</td>
        @endif
    </tr></tfoot>
    @endif
</table>
</div>

<div style="font-size:10.5px;color:var(--text-4);margin-top:10px;line-height:1.5">
    @if($showCost)“Margin lost” is the profit that was earned on these items and has now been given back.@endif
    A voided sale is reversed and removed outright, so only credit notes appear here.
</div>
</div>
@endsection
