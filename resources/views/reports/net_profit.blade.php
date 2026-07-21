{{-- reports/net_profit.blade.php — net profit day by day --}}
@extends('layouts.app')
@section('title','Net Profit')
@section('page-title','Reports — Net Profit (date wise)')
@section('content')
@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<div style="padding:14px 16px">

@include('reports.partials.header', [
    'title'  => 'Net profit by date',
    'icon'   => 'ti-report-money',
    'export' => 'net_profit',
])

<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:14px">
    @foreach([
        ['Net sales', 'Rs. '.number_format($totals['net']), 'var(--text)'],
        ['Cost of goods', 'Rs. '.number_format($totals['cogs']), 'var(--text-2)'],
        ['Gross profit', 'Rs. '.number_format($totals['gross']), 'var(--primary-text)'],
        ['Expenses', 'Rs. '.number_format($totals['expenses']), 'var(--warning-2)'],
        ['Net profit', 'Rs. '.number_format($totals['profit']), $totals['profit'] < 0 ? 'var(--danger)' : 'var(--success)'],
    ] as [$l,$v,$c])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:16px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:12px;min-width:900px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Date','Invoices','Sales','Returns','Net sales','Cost of goods','Gross profit','Expenses','Net profit','Margin'] as $i => $h)
        <th style="padding:9px 10px;text-align:{{ $i === 0 ? 'left' : 'right' }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $r)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 10px;color:var(--text);white-space:nowrap">{{ \Carbon\Carbon::parse($r['date'])->format('D, d M Y') }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text-3)">{{ $r['invoices'] }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text-2)">{{ $money($r['sales']) }}</td>
        <td style="padding:8px 10px;text-align:right;color:{{ $r['returns'] > 0 ? 'var(--danger)' : 'var(--text-4)' }}">{{ $money($r['returns']) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text)">{{ $money($r['net']) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text-3)">{{ $money($r['cogs']) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--primary-text)">{{ $money($r['gross']) }}</td>
        <td style="padding:8px 10px;text-align:right;color:{{ $r['expenses'] > 0 ? 'var(--warning-2)' : 'var(--text-4)' }}">{{ $money($r['expenses']) }}</td>
        <td style="padding:8px 10px;text-align:right;font-weight:500;color:{{ $r['profit'] < 0 ? 'var(--danger)' : 'var(--success)' }}">{{ $money($r['profit']) }}</td>
        <td style="padding:8px 10px;text-align:right;color:{{ $r['margin'] < 0 ? 'var(--danger)' : 'var(--text-2)' }}">{{ $r['margin'] }}%</td>
    </tr>
    @empty
    <tr><td colspan="10" style="padding:28px;text-align:center;color:var(--text-4)">Nothing traded in this period.</td></tr>
    @endforelse
    </tbody>
    @if($rows->count())
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td style="padding:9px 10px;color:var(--text-2);font-weight:500">Total</td>
        <td style="padding:9px 10px;text-align:right;color:var(--text-3);font-weight:500">{{ $totals['invoices'] }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--text-2);font-weight:500">{{ $money($totals['sales']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--danger);font-weight:500">{{ $money($totals['returns']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--text);font-weight:600">{{ $money($totals['net']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--text-3);font-weight:500">{{ $money($totals['cogs']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--primary-text);font-weight:600">{{ $money($totals['gross']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--warning-2);font-weight:500">{{ $money($totals['expenses']) }}</td>
        <td style="padding:9px 10px;text-align:right;font-weight:600;color:{{ $totals['profit'] < 0 ? 'var(--danger)' : 'var(--success)' }}">{{ $money($totals['profit']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--text-2);font-weight:500">{{ $totals['margin'] }}%</td>
    </tr></tfoot>
    @endif
</table>
</div>

<div style="font-size:10.5px;color:var(--text-4);margin-top:10px;line-height:1.5">
    Net sales are takings less refunds; cost of goods is what those items actually cost, net of
    what came back. Expenses are what was spent that day, so net profit is the money the shop
    genuinely kept.
</div>
</div>
@endsection
