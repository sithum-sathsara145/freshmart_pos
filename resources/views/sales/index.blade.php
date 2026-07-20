{{-- sales/index.blade.php --}}
@extends('layouts.app')
@section('title','Sales')
@section('page-title','Sales')

@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Today's sales</div>
        <div style="font-size:18px;font-weight:500;color:var(--text)">Rs. {{ number_format($stats['today_total']) }}</div>
    </div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Invoices today</div>
        <div style="font-size:18px;font-weight:500;color:var(--text)">{{ $stats['today_count'] }}</div>
    </div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">This month</div>
        <div style="font-size:18px;font-weight:500;color:var(--success)">Rs. {{ number_format($stats['month_total']) }}</div>
    </div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Pending dues</div>
        <div style="font-size:18px;font-weight:500;color:var(--warning)">Rs. {{ number_format($stats['pending_dues']) }}</div>
        @if(($stats['credit_no_doc'] ?? 0) > 0)
        <a href="{{ route('sales.index', ['filter' => 'credit_no_doc']) }}" style="display:inline-flex;align-items:center;gap:3px;margin-top:5px;font-size:10px;color:var(--danger);text-decoration:none" title="Credit sales awaiting the signed copy">
            <i class="ti ti-alert-triangle" style="font-size:11px"></i> {{ $stats['credit_no_doc'] }} awaiting signed copy
        </a>
        @endif
    </div>
</div>

<div style="display:flex;gap:8px;margin-bottom:12px;align-items:center">
    <form method="GET" style="display:flex;gap:8px;flex:1">
        <div style="flex:1;display:flex;align-items:center;gap:7px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;padding:0 10px;height:34px">
            <i class="ti ti-search" style="font-size:13px;color:var(--text-3)"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Invoice #, customer name..."
                style="background:none;border:none;outline:none;color:var(--text);font-size:12px;width:100%">
        </div>
        <select name="status" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
            <option value="">All status</option>
            <option value="paid" {{ request('status')=='paid'?'selected':'' }}>Paid</option>
            <option value="partial" {{ request('status')=='partial'?'selected':'' }}>Partial</option>
            <option value="returned" {{ request('status')=='returned'?'selected':'' }}>Returned</option>
        </select>
        <input type="date" name="from_date" value="{{ request('from_date') }}" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
        <input type="date" name="to_date" value="{{ request('to_date') }}" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
        <button type="submit" style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Filter</button>
    </form>
    @can('pos.access')
    <a href="{{ route('pos') }}" style="height:34px;padding:0 14px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-scan" style="font-size:13px"></i>Open POS
    </a>
    @endcan
    @can('sales.create')
    <a href="{{ route('sales.create') }}" style="height:34px;padding:0 14px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>New Sale
    </a>
    @endcan
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead>
        <tr style="border-bottom:.5px solid var(--border)">
            @foreach(['Invoice #','Date','Customer','Items','Discount','Total','Paid','Status','Actions'] as $h)
            <th style="padding:9px 12px;text-align:{{ in_array($h,['Total','Paid']) ? 'right' : (in_array($h,['Items','Status','Actions']) ? 'center' : 'left') }};color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($sales as $sale)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:9px 12px;color:var(--success);font-weight:500">{{ $sale->invoice_no }}</td>
            <td style="padding:9px 12px;color:var(--text-3)">{{ $sale->created_at->format('d M H:i') }}</td>
            <td style="padding:9px 12px;color:var(--text)">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
            <td style="padding:9px 12px;text-align:center;color:var(--text-2)">{{ $sale->items_count ?? '—' }}</td>
            <td style="padding:9px 12px;color:var(--success)">{{ $sale->discount_amount > 0 ? 'Rs. '.number_format($sale->discount_amount) : '—' }}</td>
            <td style="padding:9px 12px;text-align:right;color:var(--text);font-weight:500">Rs. {{ number_format($sale->total) }}</td>
            <td style="padding:9px 12px;text-align:right;color:{{ $sale->paid_amount >= $sale->total ? 'var(--success)' : 'var(--warning)' }};font-weight:500">Rs. {{ number_format($sale->paid_amount) }}</td>
            <td style="padding:9px 12px;text-align:center">
                <span style="font-size:10px;padding:2px 7px;border-radius:10px;font-weight:500;
                    background:{{ ['paid'=>'var(--success-soft)','partial'=>'var(--warning-soft)','returned'=>'var(--danger-soft)'][$sale->status] ?? 'var(--surface-2)' }};
                    color:{{ ['paid'=>'var(--success)','partial'=>'var(--warning)','returned'=>'var(--danger-text)'][$sale->status] ?? 'var(--text-2)' }}">
                    {{ ucfirst($sale->status) }}
                </span>
            </td>
            <td style="padding:9px 12px;text-align:center">
                <div style="display:flex;gap:3px;justify-content:center">
                    <a href="{{ route('sales.show',$sale) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                    <a href="{{ route('sales.receipt',$sale->id) }}" target="_blank" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-printer" style="font-size:12px"></i></a>
                    <a href="{{ route('sales.invoice',$sale->id) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--info);text-decoration:none"><i class="ti ti-file-invoice" style="font-size:12px"></i></a>
                    @if($sale->status !== 'returned')
                    @can('sale_returns.create')
                    <a href="{{ route('sale-returns.create') }}?sale_id={{ $sale->id }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--danger);text-decoration:none"><i class="ti ti-arrow-back-up" style="font-size:12px"></i></a>
                    @endcan
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" style="padding:32px;text-align:center;color:var(--text-4)">No sales found</td></tr>
        @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $sales->links() }}</div>
</div>
@endsection
