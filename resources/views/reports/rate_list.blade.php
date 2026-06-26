{{-- reports/rate_list.blade.php --}}
@extends('layouts.app')
@section('title','Rate List')
@section('page-title','Reports — Rate List')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;justify-content:flex-end;margin-bottom:12px;gap:8px">
    <a href="{{ route('reports.export',['rate_list','format'=>'pdf']) }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-download" style="font-size:12px"></i>Export PDF</a>
    <a href="{{ route('barcodes.bulk') }}" style="height:32px;padding:0 12px;background:#1e3a5f;color:#60a5fa;border:.5px solid #1e3a5f;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-printer" style="font-size:12px"></i>Print all barcodes</a>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Product','Category','Brand','Unit','Buy price','Sale price','Margin'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($products as $p)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $p['name'] }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:#1e3a5f;color:#60a5fa">{{ $p['category'] ?? '—' }}</span></td>
        <td style="padding:9px 12px;color:#94a3b8">{{ $p['brand'] ?? '—' }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $p['unit'] }}</td>
        <td style="padding:9px 12px;color:#64748b">Rs. {{ number_format($p['purchase_price']) }}</td>
        <td style="padding:9px 12px;color:#a5b4fc;font-weight:500">Rs. {{ number_format($p['sale_price']) }}</td>
        <td style="padding:9px 12px;color:#4ade80;font-weight:500">{{ $p['margin'] }}%</td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:32px;text-align:center;color:#4a5568">No products found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
