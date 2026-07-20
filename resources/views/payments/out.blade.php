{{-- payments/out.blade.php --}}
@extends('layouts.app')
@section('title','Payment Out')
@section('page-title','Payment Out')
@section('content')
<div style="padding:14px 16px">
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Ref #','Supplier','Bill ref','Date','Method','Amount'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payments as $p)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--primary-text);font-weight:500">{{ $p->reference_no }}</td>
        <td style="padding:9px 12px;color:var(--text)">{{ $p->purchase?->supplier?->name ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $p->purchase?->bill_no ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $p->created_at->format('d M H:i') }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:var(--surface-2);color:var(--text-2)">{{ ucfirst($p->method) }}</span></td>
        <td style="padding:9px 12px;color:var(--danger);font-weight:500">Rs. {{ number_format($p->amount) }}</td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--text-4)">No payment out records</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $payments->links() }}</div>
</div>
@endsection
