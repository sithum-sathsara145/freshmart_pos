{{-- reports/stock_summary.blade.php --}}
@extends('layouts.app')
@section('title','Stock Summary')
@section('page-title','Reports — Stock Summary')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Products</div><div style="font-size:18px;font-weight:500;color:#e2e8f0">{{ $totals['products'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Total value</div><div style="font-size:18px;font-weight:500;color:#4ade80">Rs. {{ number_format($totals['total_value']) }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Low stock</div><div style="font-size:18px;font-weight:500;color:#fb923c">{{ $totals['low'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Out of stock</div><div style="font-size:18px;font-weight:500;color:#f87171">{{ $totals['out'] }}</div></div>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Product','Category','Buy price','Qty','Value','Status'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($stocks as $s)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $s['product'] }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:#1e3a5f;color:#60a5fa">{{ $s['category']??'—' }}</span></td>
        <td style="padding:9px 12px;color:#64748b">Rs. {{ number_format($s['buy_price']) }}</td>
        <td style="padding:9px 12px;color:{{ $s['status']==='out'?'#f87171':($s['status']==='low'?'#fb923c':'#e2e8f0') }};font-weight:500">{{ $s['quantity'] }}</td>
        <td style="padding:9px 12px;color:#e2e8f0">Rs. {{ number_format($s['value']) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['out'=>'#7f1d1d','low'=>'#451a03','ok'=>'#14532d'][$s['status']] }};color:{{ ['out'=>'#fca5a5','low'=>'#fb923c','ok'=>'#4ade80'][$s['status']] }}">{{ ucfirst($s['status']) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:#4a5568">No stock data</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
