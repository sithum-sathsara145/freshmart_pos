{{-- sales/index.blade.php --}}
@extends('layouts.app')
@section('title','Sales')
@section('page-title','Sales')

@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:#64748b;margin-bottom:3px">Today's sales</div>
        <div style="font-size:18px;font-weight:500;color:#e2e8f0">Rs. {{ number_format($stats['today_total']) }}</div>
    </div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:#64748b;margin-bottom:3px">Invoices today</div>
        <div style="font-size:18px;font-weight:500;color:#e2e8f0">{{ $stats['today_count'] }}</div>
    </div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:#64748b;margin-bottom:3px">This month</div>
        <div style="font-size:18px;font-weight:500;color:#4ade80">Rs. {{ number_format($stats['month_total']) }}</div>
    </div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:#64748b;margin-bottom:3px">Pending dues</div>
        <div style="font-size:18px;font-weight:500;color:#fb923c">Rs. {{ number_format($stats['pending_dues']) }}</div>
        @if(($stats['credit_no_doc'] ?? 0) > 0)
        <a href="{{ route('sales.index', ['filter' => 'credit_no_doc']) }}" style="display:inline-flex;align-items:center;gap:3px;margin-top:5px;font-size:10px;color:#f87171;text-decoration:none" title="Credit sales awaiting the signed copy">
            <i class="ti ti-alert-triangle" style="font-size:11px"></i> {{ $stats['credit_no_doc'] }} awaiting signed copy
        </a>
        @endif
    </div>
</div>

<div style="display:flex;gap:8px;margin-bottom:12px;align-items:center">
    <form method="GET" style="display:flex;gap:8px;flex:1">
        <div style="flex:1;display:flex;align-items:center;gap:7px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;padding:0 10px;height:34px">
            <i class="ti ti-search" style="font-size:13px;color:#64748b"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Invoice #, customer name..."
                style="background:none;border:none;outline:none;color:#e2e8f0;font-size:12px;width:100%">
        </div>
        <select name="status" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
            <option value="">All status</option>
            <option value="paid" {{ request('status')=='paid'?'selected':'' }}>Paid</option>
            <option value="partial" {{ request('status')=='partial'?'selected':'' }}>Partial</option>
            <option value="returned" {{ request('status')=='returned'?'selected':'' }}>Returned</option>
        </select>
        <input type="date" name="from_date" value="{{ request('from_date') }}" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
        <input type="date" name="to_date" value="{{ request('to_date') }}" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
        <button type="submit" style="height:34px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">Filter</button>
    </form>
    <a href="{{ route('pos') }}" style="height:34px;padding:0 14px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-scan" style="font-size:13px"></i>Open POS
    </a>
    <a href="{{ route('sales.create') }}" style="height:34px;padding:0 14px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>New Sale
    </a>
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead>
        <tr style="border-bottom:.5px solid #2a2d3a">
            @foreach(['Invoice #','Date','Customer','Items','Discount','Total','Paid','Status','Actions'] as $h)
            <th style="padding:9px 12px;text-align:{{ in_array($h,['Total','Paid']) ? 'right' : (in_array($h,['Items','Status','Actions']) ? 'center' : 'left') }};color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($sales as $sale)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:9px 12px;color:#4ade80;font-weight:500">{{ $sale->invoice_no }}</td>
            <td style="padding:9px 12px;color:#64748b">{{ $sale->created_at->format('d M H:i') }}</td>
            <td style="padding:9px 12px;color:#e2e8f0">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
            <td style="padding:9px 12px;text-align:center;color:#94a3b8">{{ $sale->items_count ?? '—' }}</td>
            <td style="padding:9px 12px;color:#4ade80">{{ $sale->discount_amount > 0 ? 'Rs. '.number_format($sale->discount_amount) : '—' }}</td>
            <td style="padding:9px 12px;text-align:right;color:#e2e8f0;font-weight:500">Rs. {{ number_format($sale->total) }}</td>
            <td style="padding:9px 12px;text-align:right;color:{{ $sale->paid_amount >= $sale->total ? '#4ade80' : '#fb923c' }};font-weight:500">Rs. {{ number_format($sale->paid_amount) }}</td>
            <td style="padding:9px 12px;text-align:center">
                <span style="font-size:10px;padding:2px 7px;border-radius:10px;font-weight:500;
                    background:{{ ['paid'=>'#14532d','partial'=>'#451a03','returned'=>'#7f1d1d'][$sale->status] ?? '#1e2130' }};
                    color:{{ ['paid'=>'#4ade80','partial'=>'#fb923c','returned'=>'#fca5a5'][$sale->status] ?? '#94a3b8' }}">
                    {{ ucfirst($sale->status) }}
                </span>
            </td>
            <td style="padding:9px 12px;text-align:center">
                <div style="display:flex;gap:3px;justify-content:center">
                    <a href="{{ route('sales.show',$sale) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                    <a href="{{ route('sales.receipt',$sale->id) }}" target="_blank" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-printer" style="font-size:12px"></i></a>
                    <a href="{{ route('sales.invoice',$sale->id) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#60a5fa;text-decoration:none"><i class="ti ti-file-invoice" style="font-size:12px"></i></a>
                    @if($sale->status !== 'returned')
                    <a href="{{ route('sale-returns.create') }}?sale_id={{ $sale->id }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#f87171;text-decoration:none"><i class="ti ti-arrow-back-up" style="font-size:12px"></i></a>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" style="padding:32px;text-align:center;color:#4a5568">No sales found</td></tr>
        @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $sales->links() }}</div>
</div>
@endsection
