{{-- reports/payments.blade.php --}}
@extends('layouts.app')
@section('title','Payments Report')
@section('page-title','Reports — Payments')
@section('content')
<div style="padding:14px 16px">
<form method="GET" style="display:flex;gap:8px;margin-bottom:14px">
    <input type="date" name="from_date" value="{{ $from }}" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
    <input type="date" name="to_date" value="{{ $to }}" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
    <select name="method" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
        <option value="">All methods</option>
        @foreach(['cash','card','bank_transfer','cheque'] as $m)
        <option value="{{ $m }}" {{ request('method')===$m?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$m)) }}</option>
        @endforeach
    </select>
    <button type="submit" style="height:34px;padding:0 12px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;cursor:pointer">Apply</button>
    <a href="{{ route('reports.export',['payments','format'=>'pdf','from_date'=>$from,'to_date'=>$to]) }}" style="height:34px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none;margin-left:auto">
        <i class="ti ti-download" style="font-size:12px"></i>Export
    </a>
</form>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    @foreach([['Cash','cash','#4ade80','#14532d'],['Card','card','#60a5fa','#1e3a5f'],['Bank transfer','bank_transfer','#a5b4fc','#312e81'],['Total','all','#e2e8f0','#1e2130']] as [$l,$k,$c,$bg])
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:#64748b;margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:16px;font-weight:500;color:{{ $c }}">Rs. {{ number_format($methodTotals[$k] ?? 0) }}</div>
    </div>
    @endforeach
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead>
        <tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Date</th>
            <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Ref #</th>
            <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Type</th>
            <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Method</th>
            <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Linked to</th>
            <th style="padding:9px 12px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Amount</th>
        </tr>
    </thead>
    <tbody>
    @forelse($payments as $p)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#64748b">{{ $p->created_at->format('d M Y H:i') }}</td>
        <td style="padding:9px 12px;color:#a5b4fc">{{ $p->reference_no }}</td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->type==='payment_in'?'#14532d':'#7f1d1d' }};color:{{ $p->type==='payment_in'?'#4ade80':'#fca5a5' }}">
                {{ $p->type === 'payment_in' ? 'Payment In' : 'Payment Out' }}
            </span>
        </td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#1e2130;color:#94a3b8">
                {{ ucfirst(str_replace('_',' ',$p->method)) }}
            </span>
        </td>
        <td style="padding:9px 12px;color:#64748b">{{ $p->sale?->invoice_no ?? $p->purchase?->bill_no ?? '—' }}</td>
        <td style="padding:9px 12px;text-align:right;color:{{ $p->type==='payment_in'?'#4ade80':'#f87171' }};font-weight:500">
            Rs. {{ number_format($p->amount) }}
        </td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:#4a5568">No payments in selected period</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $payments->links() }}</div>
</div>
@endsection
