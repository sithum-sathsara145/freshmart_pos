{{-- online-orders/show.blade.php --}}
@extends('layouts.app')
@section('title','Order #'.$onlineOrder->order_no)
@section('page-title','Order — '.$onlineOrder->order_no)
@section('content')
<div style="padding:14px 16px;max-width:700px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('online-orders.index') }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrow-left" style="font-size:12px"></i>Back</a>
    @if(in_array($onlineOrder->status,['new','processing']))
    <form method="POST" action="{{ route('online-orders.status',$onlineOrder->id) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="status" value="{{ $onlineOrder->status==='new'?'processing':'dispatched' }}">
        <button type="submit" style="height:32px;padding:0 12px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;cursor:pointer">{{ $onlineOrder->status==='new'?'Start processing':'Mark dispatched' }}</button>
    </form>
    @endif
</div>
<div style="display:grid;grid-template-columns:1fr 280px;gap:12px">
<div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-bottom:10px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Order items</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)"><th style="padding:6px 0;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Product</th><th style="padding:6px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">Qty</th><th style="padding:6px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Total</th></tr></thead>
        <tbody>
        @foreach($onlineOrder->items as $item)
        <tr style="border-bottom:.5px solid var(--surface-3)"><td style="padding:8px 0;color:var(--text)">{{ $item->product?->name }}</td><td style="padding:8px 6px;text-align:center;color:var(--text-2)">{{ $item->quantity }}</td><td style="padding:8px 6px;text-align:right;color:var(--success);font-weight:500">Rs. {{ number_format($item->subtotal) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:500;color:var(--text);padding-top:8px;border-top:.5px solid var(--border);margin-top:6px"><span>Total</span><span>Rs. {{ number_format($onlineOrder->total) }}</span></div>
</div>
</div>
<div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Order info</div>
    @foreach([['Order #',$onlineOrder->order_no,'var(--info)'],['Date',$onlineOrder->created_at->format('d M Y H:i'),'var(--text-3)'],['Customer',$onlineOrder->customer?->name??$onlineOrder->customer_name,'var(--text)'],['Phone',$onlineOrder->customer_phone??'—','var(--text-2)'],['Delivery',ucfirst(str_replace('_',' ',$onlineOrder->delivery_type)),'var(--primary-text)'],['Status',ucfirst($onlineOrder->status),'var(--success)']] as [$l,$v,$c])
    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:.5px solid var(--surface-3);font-size:12px"><span style="color:var(--text-3)">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span></div>
    @endforeach
    @if($onlineOrder->customer_address)
    <div style="margin-top:8px;padding:8px;background:var(--bg);border-radius:5px;font-size:11px;color:var(--text-2)"><i class="ti ti-map-pin" style="font-size:12px;margin-right:4px;color:var(--text-3)"></i>{{ $onlineOrder->customer_address }}</div>
    @endif
</div>
</div>
</div>
</div>
@endsection
