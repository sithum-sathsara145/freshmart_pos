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
    @foreach([['This month',number_format($stats['month_total']),'#e2e8f0'],['Bills',$stats['month_count'],'#e2e8f0'],['Balance due',number_format($stats['balance_due']),'#fb923c'],['Paid',number_format($stats['paid_this_month']),'#4ade80']] as [$l,$v,$c])
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:#64748b;margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:18px;font-weight:500;color:{{ $c }}">{{ str_contains($l,'month')||str_contains($l,'due')||str_contains($l,'Paid') ? 'Rs. ' : '' }}{{ $v }}</div>
    </div>
    @endforeach
</div>
<div style="display:flex;gap:8px;margin-bottom:12px">
    <form method="GET" style="display:flex;gap:8px;flex:1">
        <div style="flex:1;display:flex;align-items:center;gap:7px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;padding:0 10px;height:34px">
            <i class="ti ti-search" style="font-size:13px;color:#64748b"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Bill #, supplier..." style="background:none;border:none;outline:none;color:#e2e8f0;font-size:12px;width:100%">
        </div>
        <select name="payment_status" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
            <option value="">All status</option>
            <option value="paid">Paid</option><option value="partial">Partial</option><option value="unpaid">Unpaid</option>
        </select>
        <button type="submit" style="height:34px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">Filter</button>
    </form>
    <a href="{{ route('purchases.create') }}" style="height:34px;padding:0 14px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>New Purchase
    </a>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Bill #','Supplier','Date','Total','Paid','Balance','Status','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($purchases as $p)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#60a5fa;font-weight:500">{{ $p->bill_no }}</td>
        <td style="padding:9px 12px;color:#e2e8f0">{{ $p->supplier->name }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ \Carbon\Carbon::parse($p->purchase_date)->format('d M Y') }}</td>
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">Rs. {{ number_format($p->total) }}</td>
        <td style="padding:9px 12px;color:#4ade80">Rs. {{ number_format($p->paid_amount) }}</td>
        <td style="padding:9px 12px;color:{{ $p->balance_due > 0 ? '#fb923c' : '#64748b' }}">Rs. {{ number_format($p->balance_due) }}</td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;font-weight:500;background:{{ ['paid'=>'#14532d','partial'=>'#451a03','unpaid'=>'#7f1d1d'][$p->payment_status]??'#1e2130' }};color:{{ ['paid'=>'#4ade80','partial'=>'#fb923c','unpaid'=>'#fca5a5'][$p->payment_status]??'#94a3b8' }}">{{ ucfirst($p->payment_status) }}</span>
        </td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:3px">
                <a href="{{ route('purchases.show',$p) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                <a href="{{ route('purchases.bill',$p->id) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#60a5fa;text-decoration:none"><i class="ti ti-file-invoice" style="font-size:12px"></i></a>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:32px;text-align:center;color:#4a5568">No purchases found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $purchases->links() }}</div>
</div>
@endsection
