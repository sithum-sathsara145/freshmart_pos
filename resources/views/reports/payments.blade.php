{{-- reports/payments.blade.php --}}
@extends('layouts.app')
@section('title','Payments Report')
@section('page-title','Reports — Payments')
@section('content')
<div style="padding:14px 16px">
<form method="GET" style="display:flex;gap:8px;margin-bottom:14px">
    <input type="date" name="from_date" value="{{ $from }}" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
    <input type="date" name="to_date" value="{{ $to }}" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
    <select name="method" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
        <option value="">All methods</option>
        @foreach(['cash','card','bank_transfer','cheque'] as $m)
        <option value="{{ $m }}" {{ request('method')===$m?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$m)) }}</option>
        @endforeach
    </select>
    <button type="submit" style="height:34px;padding:0 12px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;cursor:pointer">Apply</button>
    <a href="{{ route('reports.export',['payments','format'=>'pdf','from_date'=>$from,'to_date'=>$to]) }}" style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none;margin-left:auto">
        <i class="ti ti-download" style="font-size:12px"></i>Export
    </a>
</form>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    @foreach([['Cash','cash','var(--success)','var(--success-soft)'],['Card','card','var(--info)','var(--info-soft)'],['Bank transfer','bank_transfer','var(--primary-text)','var(--primary-soft)'],['Total','all','var(--text)','var(--surface-2)']] as [$l,$k,$c,$bg])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:16px;font-weight:500;color:{{ $c }}">Rs. {{ number_format($methodTotals[$k] ?? 0) }}</div>
    </div>
    @endforeach
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead>
        <tr style="border-bottom:.5px solid var(--border)">
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Date</th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Ref #</th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Type</th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Method</th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Linked to</th>
            <th style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Amount</th>
        </tr>
    </thead>
    <tbody>
    @forelse($payments as $p)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text-3)">{{ $p->created_at->format('d M Y H:i') }}</td>
        <td style="padding:9px 12px;color:var(--primary-text)">{{ $p->reference_no }}</td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->type==='payment_in'?'var(--success-soft)':'var(--danger-soft)' }};color:{{ $p->type==='payment_in'?'var(--success)':'var(--danger-text)' }}">
                {{ $p->type === 'payment_in' ? 'Payment In' : 'Payment Out' }}
            </span>
        </td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:var(--surface-2);color:var(--text-2)">
                {{ ucfirst(str_replace('_',' ',$p->method)) }}
            </span>
        </td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $p->sale?->invoice_no ?? $p->purchase?->bill_no ?? '—' }}</td>
        <td style="padding:9px 12px;text-align:right;color:{{ $p->type==='payment_in'?'var(--success)':'var(--danger)' }};font-weight:500">
            Rs. {{ number_format($p->amount) }}
        </td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--text-4)">No payments in selected period</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $payments->links() }}</div>
</div>
@endsection
