{{-- online-orders/index.blade.php --}}
@extends('layouts.app')
@section('title','Online Orders')
@section('page-title','Online Orders')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px"><i class="ti ti-bell" style="font-size:11px;color:var(--warning)"></i> New orders</div><div style="font-size:18px;font-weight:500;color:var(--warning)">{{ $stats['new'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Processing</div><div style="font-size:18px;font-weight:500;color:var(--info)">{{ $stats['processing'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Delivered today</div><div style="font-size:18px;font-weight:500;color:var(--success)">{{ $stats['delivered'] }}</div></div>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Order #','Customer','Items','Total','Delivery','Date','Status','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($orders as $o)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--info);font-weight:500">{{ $o->order_no }}</td>
        <td style="padding:9px 12px;color:var(--text)">{{ $o->customer?->name ?? $o->customer_name }}</td>
        <td style="padding:9px 12px;text-align:center;color:var(--text-2)">{{ $o->items->count() }}</td>
        <td style="padding:9px 12px;color:var(--text);font-weight:500">Rs. {{ number_format($o->total) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $o->delivery_type==='home_delivery'?'var(--primary-soft)':'var(--info-soft)' }};color:{{ $o->delivery_type==='home_delivery'?'var(--primary-text)':'var(--info)' }}">{{ $o->delivery_type === 'home_delivery' ? 'Delivery' : 'Pickup' }}</span></td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $o->created_at->format('d M H:i') }}</td>
        <td style="padding:9px 12px">
            @php $colors=['new'=>['var(--warning-soft)','var(--warning)'],'processing'=>['var(--info-soft)','var(--info)'],'dispatched'=>['var(--primary-soft)','var(--primary-text)'],'delivered'=>['var(--success-soft)','var(--success)'],'cancelled'=>['var(--danger-soft)','var(--danger-text)']]; $c=$colors[$o->status]??['var(--surface-2)','var(--text-2)']; @endphp
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $c[0] }};color:{{ $c[1] }}">{{ ucfirst($o->status) }}</span>
        </td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:3px">
                <a href="{{ route('online-orders.show',$o) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                @if($o->status === 'new')
                <form method="POST" action="{{ route('online-orders.status',$o->id) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="processing">
                    <button type="submit" style="width:26px;height:26px;background:var(--success-soft);border:.5px solid var(--success-border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--success);cursor:pointer"><i class="ti ti-check" style="font-size:12px"></i></button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:32px;text-align:center;color:var(--text-4)">No online orders</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $orders->links() }}</div>
</div>
@endsection
