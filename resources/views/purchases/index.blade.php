{{--
  REMAINING VIEWS — all in one batch file
  Copy each section to its correct path as shown in the comment above each view.
--}}

{{-- ══════════════════════════════════════════════════
  purchases/index.blade.php
══════════════════════════════════════════════════ --}}
@extends('layouts.app')
@section('title','Purchases')
@section('page-title','Purchases')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    @foreach([['This month',number_format($stats['month_total']),'var(--text)'],['Bills',$stats['month_count'],'var(--text)'],['Balance due',number_format($stats['balance_due']),'var(--warning)'],['Paid',number_format($stats['paid_this_month']),'var(--success)']] as [$l,$v,$c])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:18px;font-weight:500;color:{{ $c }}">{{ str_contains($l,'month')||str_contains($l,'due')||str_contains($l,'Paid') ? 'Rs. ' : '' }}{{ $v }}</div>
    </div>
    @endforeach
</div>
<div style="display:flex;gap:8px;margin-bottom:12px">
    <form method="GET" style="display:flex;gap:8px;flex:1">
        <div style="flex:1;display:flex;align-items:center;gap:7px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;padding:0 10px;height:34px">
            <i class="ti ti-search" style="font-size:13px;color:var(--text-3)"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Bill #, supplier..." style="background:none;border:none;outline:none;color:var(--text);font-size:12px;width:100%">
        </div>
        <select name="payment_status" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
            <option value="">All status</option>
            <option value="paid">Paid</option><option value="partial">Partial</option><option value="unpaid">Unpaid</option>
        </select>
        <button type="submit" style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Filter</button>
    </form>
    <a href="{{ route('purchases.create') }}" style="height:34px;padding:0 14px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>New Purchase
    </a>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Bill #','Supplier','Date','Total','Paid','Balance','Status','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($purchases as $p)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--info);font-weight:500">{{ $p->bill_no }}</td>
        <td style="padding:9px 12px;color:var(--text)">{{ $p->supplier->name }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ \Carbon\Carbon::parse($p->purchase_date)->format('d M Y') }}</td>
        <td style="padding:9px 12px;color:var(--text);font-weight:500">Rs. {{ number_format($p->total) }}</td>
        <td style="padding:9px 12px;color:var(--success)">Rs. {{ number_format($p->paid_amount) }}</td>
        <td style="padding:9px 12px;color:{{ $p->balance_due > 0 ? 'var(--warning)' : 'var(--text-3)' }}">Rs. {{ number_format($p->balance_due) }}</td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;font-weight:500;background:{{ ['paid'=>'var(--success-soft)','partial'=>'var(--warning-soft)','unpaid'=>'var(--danger-soft)'][$p->payment_status]??'var(--surface-2)' }};color:{{ ['paid'=>'var(--success)','partial'=>'var(--warning)','unpaid'=>'var(--danger-text)'][$p->payment_status]??'var(--text-2)' }}">{{ ucfirst($p->payment_status) }}</span>
        </td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:3px">
                <a href="{{ route('purchases.show',$p) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                <a href="{{ route('purchases.bill',$p->id) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--info);text-decoration:none"><i class="ti ti-file-invoice" style="font-size:12px"></i></a>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:32px;text-align:center;color:var(--text-4)">No purchases found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $purchases->links() }}</div>
</div>
@endsection
