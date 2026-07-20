{{-- stock/index.blade.php --}}
@extends('layouts.app')
@section('title','Stock')
@section('page-title','Stock Management')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Total products</div><div style="font-size:18px;font-weight:500;color:var(--text)">{{ $totals['products'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Stock value</div><div style="font-size:18px;font-weight:500;color:var(--success)">Rs. {{ number_format($totals['total_value']) }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Low stock</div><div style="font-size:18px;font-weight:500;color:var(--warning)">{{ $totals['low'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Out of stock</div><div style="font-size:18px;font-weight:500;color:var(--danger)">{{ $totals['out'] }}</div></div>
</div>
<div style="display:flex;gap:8px;margin-bottom:12px">
    @can('stock.adjust')
    <a href="{{ route('stock.adjustments') }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-adjustments" style="font-size:13px"></i>Adjustments</a>
    @endcan
    @can('stock.transfer')
    <a href="{{ route('stock.transfers') }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrows-exchange" style="font-size:13px"></i>Transfers</a>
    @endcan
    @can('reports.view')
    <a href="{{ route('reports.stock_alert') }}" style="height:32px;padding:0 12px;background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-alert-triangle" style="font-size:13px"></i>Alerts</a>
    @endcan
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Product','Category','Brand','Unit','Current stock','Value','Min','Status'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($stocks as $s)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--text);font-weight:500">{{ $s->product->name }}</td>
        <td style="padding:8px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:var(--info-soft);color:var(--info)">{{ $s->product->category?->name ?? '—' }}</span></td>
        <td style="padding:8px 12px;color:var(--text-2)">{{ $s->product->brand?->name ?? '—' }}</td>
        <td style="padding:8px 12px;color:var(--text-3)">{{ $s->product->unit }}</td>
        <td style="padding:8px 12px;font-weight:500;color:{{ $s->quantity <= 0 ? 'var(--danger)' : ($s->quantity < $s->product->min_stock ? 'var(--warning)' : 'var(--text)') }}">{{ $s->quantity }}</td>
        <td style="padding:8px 12px;color:var(--text-3)">Rs. {{ number_format($s->quantity * $s->product->purchase_price) }}</td>
        <td style="padding:8px 12px;color:var(--text-3)">{{ $s->product->min_stock }}</td>
        <td style="padding:8px 12px">
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;font-weight:500;background:{{ $s->quantity <= 0 ? 'var(--danger-soft)' : ($s->quantity < $s->product->min_stock ? 'var(--warning-soft)' : 'var(--success-soft)') }};color:{{ $s->quantity <= 0 ? 'var(--danger-text)' : ($s->quantity < $s->product->min_stock ? 'var(--warning)' : 'var(--success)') }}">
                {{ $s->quantity <= 0 ? 'Out' : ($s->quantity < $s->product->min_stock ? 'Low' : 'OK') }}
            </span>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:32px;text-align:center;color:var(--text-4)">No stock records</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $stocks->links() }}</div>
</div>
@endsection
