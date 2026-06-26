{{-- reports/stock_alert.blade.php --}}
@extends('layouts.app')
@section('title','Stock Alert')
@section('page-title','Reports — Stock Alert')
@section('content')
<div style="padding:14px 16px">
<div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
    <div style="font-size:12px;color:#64748b">{{ count($alerts) }} products below minimum stock level</div>
    <a href="{{ route('reports.export',['stock_alert','format'=>'pdf']) }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-download" style="font-size:12px"></i>Export</a>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Product','Category','Current stock','Min level','Status','Action'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($alerts as $a)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $a['name'] }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:#1e3a5f;color:#60a5fa">{{ $a['category'] ?? '—' }}</span></td>
        <td style="padding:9px 12px;font-weight:500;color:{{ $a['current_stock'] <= 0 ? '#f87171' : '#fb923c' }}">{{ $a['current_stock'] }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $a['min_stock'] }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $a['status']==='out'?'#7f1d1d':'#451a03' }};color:{{ $a['status']==='out'?'#fca5a5':'#fb923c' }}">{{ $a['status']==='out' ? 'Out of stock' : 'Low stock' }}</span></td>
        <td style="padding:9px 12px"><a href="{{ route('purchases.create') }}?product_id={{ $a['id'] }}" style="height:26px;padding:0 10px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:5px;font-size:11px;display:flex;align-items:center;text-decoration:none;width:fit-content">Reorder</a></td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:#4a5568">✅ All products are well stocked</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
