{{-- payments/out.blade.php --}}
@extends('layouts.app')
@section('title','Payment Out')
@section('page-title','Payment Out')
@section('content')
<div style="padding:14px 16px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Ref #','Supplier','Bill ref','Date','Method','Amount'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payments as $p)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#a5b4fc;font-weight:500">{{ $p->reference_no }}</td>
        <td style="padding:9px 12px;color:#e2e8f0">{{ $p->purchase?->supplier?->name ?? '—' }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $p->purchase?->bill_no ?? '—' }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $p->created_at->format('d M H:i') }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#1e2130;color:#94a3b8">{{ ucfirst($p->method) }}</span></td>
        <td style="padding:9px 12px;color:#f87171;font-weight:500">Rs. {{ number_format($p->amount) }}</td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:#4a5568">No payment out records</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $payments->links() }}</div>
</div>
@endsection
