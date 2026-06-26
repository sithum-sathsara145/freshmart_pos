{{-- accounts/transactions.blade.php --}}
@extends('layouts.app')
@section('title','Transactions')
@section('page-title','Account — '.$account->name)
@section('content')
<div style="padding:14px 16px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center">
    <div><div style="font-size:11px;color:#64748b;margin-bottom:3px">Current balance</div><div style="font-size:22px;font-weight:500;color:#4ade80">Rs. {{ number_format($account->balance) }}</div></div>
    <a href="{{ route('accounts.index') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrow-left" style="font-size:12px"></i>All accounts</a>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Date','Description','Type','In','Out','Balance'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($payments as $p)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#64748b">{{ $p->created_at->format('d M H:i') }}</td>
        <td style="padding:9px 12px;color:#e2e8f0">{{ $p->notes ?? ($p->sale?->invoice_no ?? $p->purchase?->bill_no ?? 'Transfer') }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->type==='payment_in'?'#14532d':($p->type==='payment_out'?'#7f1d1d':'#312e81') }};color:{{ $p->type==='payment_in'?'#4ade80':($p->type==='payment_out'?'#fca5a5':'#a5b4fc') }}">{{ str_replace('_',' ',ucfirst($p->type)) }}</span></td>
        <td style="padding:9px 12px;color:#4ade80">{{ $p->type==='payment_in' ? 'Rs. '.number_format($p->amount) : '—' }}</td>
        <td style="padding:9px 12px;color:#f87171">{{ $p->type==='payment_out' ? 'Rs. '.number_format($p->amount) : '—' }}</td>
        <td style="padding:9px 12px;color:#94a3b8">—</td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:#4a5568">No transactions</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $payments->links() }}</div>
</div>
@endsection
