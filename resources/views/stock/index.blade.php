{{-- stock/index.blade.php --}}
@extends('layouts.app')
@section('title','Stock')
@section('page-title','Stock Management')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Total products</div><div style="font-size:18px;font-weight:500;color:#e2e8f0">{{ $totals['products'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Stock value</div><div style="font-size:18px;font-weight:500;color:#4ade80">Rs. {{ number_format($totals['total_value']) }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Low stock</div><div style="font-size:18px;font-weight:500;color:#fb923c">{{ $totals['low'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Out of stock</div><div style="font-size:18px;font-weight:500;color:#f87171">{{ $totals['out'] }}</div></div>
</div>
<div style="display:flex;gap:8px;margin-bottom:12px">
    <a href="{{ route('stock.adjustments') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-adjustments" style="font-size:13px"></i>Adjustments</a>
    <a href="{{ route('stock.transfers') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrows-exchange" style="font-size:13px"></i>Transfers</a>
    <a href="{{ route('reports.stock_alert') }}" style="height:32px;padding:0 12px;background:#7f1d1d;color:#fca5a5;border:.5px solid #991b1b;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-alert-triangle" style="font-size:13px"></i>Alerts</a>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Product','Category','Brand','Unit','Current stock','Value','Min','Status'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($stocks as $s)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:8px 12px;color:#e2e8f0;font-weight:500">{{ $s->product->name }}</td>
        <td style="padding:8px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:#1e3a5f;color:#60a5fa">{{ $s->product->category?->name ?? '—' }}</span></td>
        <td style="padding:8px 12px;color:#94a3b8">{{ $s->product->brand?->name ?? '—' }}</td>
        <td style="padding:8px 12px;color:#64748b">{{ $s->product->unit }}</td>
        <td style="padding:8px 12px;font-weight:500;color:{{ $s->quantity <= 0 ? '#f87171' : ($s->quantity < $s->product->min_stock ? '#fb923c' : '#e2e8f0') }}">{{ $s->quantity }}</td>
        <td style="padding:8px 12px;color:#64748b">Rs. {{ number_format($s->quantity * $s->product->purchase_price) }}</td>
        <td style="padding:8px 12px;color:#64748b">{{ $s->product->min_stock }}</td>
        <td style="padding:8px 12px">
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;font-weight:500;background:{{ $s->quantity <= 0 ? '#7f1d1d' : ($s->quantity < $s->product->min_stock ? '#451a03' : '#14532d') }};color:{{ $s->quantity <= 0 ? '#fca5a5' : ($s->quantity < $s->product->min_stock ? '#fb923c' : '#4ade80') }}">
                {{ $s->quantity <= 0 ? 'Out' : ($s->quantity < $s->product->min_stock ? 'Low' : 'OK') }}
            </span>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:32px;text-align:center;color:#4a5568">No stock records</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $stocks->links() }}</div>
</div>
@endsection
