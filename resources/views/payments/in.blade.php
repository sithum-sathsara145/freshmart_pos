{{-- payments/in.blade.php --}}
@extends('layouts.app')
@section('title','Payment In')
@section('page-title','Payment In')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Cash received</div><div style="font-size:18px;font-weight:500;color:#4ade80">Rs. {{ number_format($totals['cash']) }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Card received</div><div style="font-size:18px;font-weight:500;color:#60a5fa">Rs. {{ number_format($totals['card']) }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Total in</div><div style="font-size:18px;font-weight:500;color:#e2e8f0">Rs. {{ number_format($totals['cash']+$totals['card']) }}</div></div>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Ref #','Customer','Invoice ref','Date','Method','Amount'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payments as $p)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#a5b4fc;font-weight:500">{{ $p->reference_no }}</td>
        <td style="padding:9px 12px;color:#e2e8f0">{{ $p->sale?->customer?->name ?? 'Walk-in' }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $p->sale?->invoice_no ?? '—' }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $p->created_at->format('d M H:i') }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->method==='cash'?'#14532d':'#1e3a5f' }};color:{{ $p->method==='cash'?'#4ade80':'#60a5fa' }}">{{ ucfirst($p->method) }}</span></td>
        <td style="padding:9px 12px;color:#4ade80;font-weight:500">Rs. {{ number_format($p->amount) }}</td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:#4a5568">No payments</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $payments->links() }}</div>
</div>
@endsection
