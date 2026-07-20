{{-- reports/stock_summary.blade.php --}}
@extends('layouts.app')
@section('title','Stock Summary')
@section('page-title','Reports — Stock Summary')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Products</div><div style="font-size:18px;font-weight:500;color:var(--text)">{{ $totals['products'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Total value</div><div style="font-size:18px;font-weight:500;color:var(--success)">Rs. {{ number_format($totals['total_value']) }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Low stock</div><div style="font-size:18px;font-weight:500;color:var(--warning)">{{ $totals['low'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Out of stock</div><div style="font-size:18px;font-weight:500;color:var(--danger)">{{ $totals['out'] }}</div></div>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Product','Category','Buy price','Qty','Value','Status'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($stocks as $s)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text);font-weight:500">{{ $s['product'] }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:var(--info-soft);color:var(--info)">{{ $s['category']??'—' }}</span></td>
        <td style="padding:9px 12px;color:var(--text-3)">Rs. {{ number_format($s['buy_price']) }}</td>
        <td style="padding:9px 12px;color:{{ $s['status']==='out'?'var(--danger)':($s['status']==='low'?'var(--warning)':'var(--text)') }};font-weight:500">{{ $s['quantity'] }}</td>
        <td style="padding:9px 12px;color:var(--text)">Rs. {{ number_format($s['value']) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['out'=>'var(--danger-soft)','low'=>'var(--warning-soft)','ok'=>'var(--success-soft)'][$s['status']] }};color:{{ ['out'=>'var(--danger-text)','low'=>'var(--warning)','ok'=>'var(--success)'][$s['status']] }}">{{ ucfirst($s['status']) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--text-4)">No stock data</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
