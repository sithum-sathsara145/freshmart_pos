{{-- reports/sales_summary.blade.php --}}
@extends('layouts.app')
@section('title','Sales Summary')
@section('page-title','Reports — Sales Summary')
@section('content')
<div style="padding:14px 16px">
<form method="GET" style="display:flex;gap:8px;margin-bottom:14px">
    <input type="date" name="from_date" value="{{ $from }}" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
    <input type="date" name="to_date" value="{{ $to }}" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
    <button type="submit" style="height:34px;padding:0 12px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;cursor:pointer">Apply</button>
    <a href="{{ route('reports.export',['sales','format'=>'pdf','from_date'=>$from,'to_date'=>$to]) }}" style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none;margin-left:auto"><i class="ti ti-download" style="font-size:12px"></i>Export</a>
</form>
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Total sales</div><div style="font-size:18px;font-weight:500;color:var(--text)">{{ $totals->count??0 }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Gross value</div><div style="font-size:18px;font-weight:500;color:var(--text)">Rs. {{ number_format($totals->total??0) }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Returns</div><div style="font-size:18px;font-weight:500;color:var(--danger)">Rs. {{ number_format($returnAmount??0) }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Net value</div><div style="font-size:18px;font-weight:500;color:var(--success)">Rs. {{ number_format($netTotal??0) }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Discounts given</div><div style="font-size:18px;font-weight:500;color:var(--warning)">Rs. {{ number_format($totals->discount??0) }}</div></div>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Invoice','Date','Customer','Cashier','Total','Status'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($sales as $s)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--success)">{{ $s->invoice_no }}</td>
        <td style="padding:8px 12px;color:var(--text-3)">{{ $s->created_at->format('d M Y') }}</td>
        <td style="padding:8px 12px;color:var(--text)">{{ $s->customer?->name??'Walk-in' }}</td>
        <td style="padding:8px 12px;color:var(--text-2)">{{ $s->user?->name }}</td>
        <td style="padding:8px 12px;color:var(--text);font-weight:500">Rs. {{ number_format($s->total) }}</td>
        <td style="padding:8px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $s->status==='paid'?'var(--success-soft)':'var(--warning-soft)' }};color:{{ $s->status==='paid'?'var(--success)':'var(--warning)' }}">{{ ucfirst($s->status) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--text-4)">No sales in this period</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $sales->links() }}</div>
</div>
@endsection
