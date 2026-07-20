{{-- reports/rate_list.blade.php --}}
@extends('layouts.app')
@section('title','Rate List')
@section('page-title','Reports — Rate List')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;justify-content:flex-end;margin-bottom:12px;gap:8px">
    <a href="{{ route('reports.export',['rate_list','format'=>'pdf']) }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-download" style="font-size:12px"></i>Export PDF</a>
    <a href="{{ route('barcodes.bulk') }}" style="height:32px;padding:0 12px;background:var(--info-soft);color:var(--info);border:.5px solid var(--info-soft);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-printer" style="font-size:12px"></i>Print all barcodes</a>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Product','Category','Brand','Unit','Buy price','Sale price','Margin'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($products as $p)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text);font-weight:500">{{ $p['name'] }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:var(--info-soft);color:var(--info)">{{ $p['category'] ?? '—' }}</span></td>
        <td style="padding:9px 12px;color:var(--text-2)">{{ $p['brand'] ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $p['unit'] }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">Rs. {{ number_format($p['purchase_price']) }}</td>
        <td style="padding:9px 12px;color:var(--primary-text);font-weight:500">Rs. {{ number_format($p['sale_price']) }}</td>
        <td style="padding:9px 12px;color:var(--success);font-weight:500">{{ $p['margin'] }}%</td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:32px;text-align:center;color:var(--text-4)">No products found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
