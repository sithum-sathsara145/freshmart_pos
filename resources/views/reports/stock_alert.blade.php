{{-- reports/stock_alert.blade.php --}}
@extends('layouts.app')
@section('title','Stock Alert')
@section('page-title','Reports — Stock Alert')
@section('content')
<div style="padding:14px 16px">
<div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
    <div style="font-size:12px;color:var(--text-3)">{{ count($alerts) }} products below minimum stock level</div>
    <a href="{{ route('reports.export',['stock_alert','format'=>'pdf']) }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-download" style="font-size:12px"></i>Export</a>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Product','Category','Current stock','Min level','Status','Action'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($alerts as $a)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text);font-weight:500">{{ $a['name'] }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:var(--info-soft);color:var(--info)">{{ $a['category'] ?? '—' }}</span></td>
        <td style="padding:9px 12px;font-weight:500;color:{{ $a['current_stock'] <= 0 ? 'var(--danger)' : 'var(--warning)' }}">{{ $a['current_stock'] }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $a['min_stock'] }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $a['status']==='out'?'var(--danger-soft)':'var(--warning-soft)' }};color:{{ $a['status']==='out'?'var(--danger-text)':'var(--warning)' }}">{{ $a['status']==='out' ? 'Out of stock' : 'Low stock' }}</span></td>
        <td style="padding:9px 12px"><a href="{{ route('purchases.create') }}?product_id={{ $a['id'] }}" style="height:26px;padding:0 10px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:5px;font-size:11px;display:flex;align-items:center;text-decoration:none;width:fit-content">Reorder</a></td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--text-4)">✅ All products are well stocked</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
