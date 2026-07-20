{{-- payments/in.blade.php --}}
@extends('layouts.app')
@section('title','Payment In')
@section('page-title','Payment In')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Cash received</div><div style="font-size:18px;font-weight:500;color:var(--success)">Rs. {{ number_format($totals['cash']) }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Card received</div><div style="font-size:18px;font-weight:500;color:var(--info)">Rs. {{ number_format($totals['card']) }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Total in</div><div style="font-size:18px;font-weight:500;color:var(--text)">Rs. {{ number_format($totals['cash']+$totals['card']) }}</div></div>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Ref #','Customer','Invoice ref','Date','Method','Amount'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payments as $p)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--primary-text);font-weight:500">{{ $p->reference_no }}</td>
        <td style="padding:9px 12px;color:var(--text)">{{ $p->sale?->customer?->name ?? 'Walk-in' }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $p->sale?->invoice_no ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $p->created_at->format('d M H:i') }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->method==='cash'?'var(--success-soft)':'var(--info-soft)' }};color:{{ $p->method==='cash'?'var(--success)':'var(--info)' }}">{{ ucfirst($p->method) }}</span></td>
        <td style="padding:9px 12px;color:var(--success);font-weight:500">Rs. {{ number_format($p->amount) }}</td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--text-4)">No payments</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $payments->links() }}</div>
</div>
@endsection
