{{-- online-orders/index.blade.php --}}
@extends('layouts.app')
@section('title','Online Orders')
@section('page-title','Online Orders')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px"><i class="ti ti-bell" style="font-size:11px;color:#fb923c"></i> New orders</div><div style="font-size:18px;font-weight:500;color:#fb923c">{{ $stats['new'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Processing</div><div style="font-size:18px;font-weight:500;color:#60a5fa">{{ $stats['processing'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Delivered today</div><div style="font-size:18px;font-weight:500;color:#4ade80">{{ $stats['delivered'] }}</div></div>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Order #','Customer','Items','Total','Delivery','Date','Status','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($orders as $o)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#60a5fa;font-weight:500">{{ $o->order_no }}</td>
        <td style="padding:9px 12px;color:#e2e8f0">{{ $o->customer?->name ?? $o->customer_name }}</td>
        <td style="padding:9px 12px;text-align:center;color:#94a3b8">{{ $o->items->count() }}</td>
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">Rs. {{ number_format($o->total) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $o->delivery_type==='home_delivery'?'#312e81':'#1e3a5f' }};color:{{ $o->delivery_type==='home_delivery'?'#a5b4fc':'#60a5fa' }}">{{ $o->delivery_type === 'home_delivery' ? 'Delivery' : 'Pickup' }}</span></td>
        <td style="padding:9px 12px;color:#64748b">{{ $o->created_at->format('d M H:i') }}</td>
        <td style="padding:9px 12px">
            @php $colors=['new'=>['#451a03','#fb923c'],'processing'=>['#1e3a5f','#60a5fa'],'dispatched'=>['#312e81','#a5b4fc'],'delivered'=>['#14532d','#4ade80'],'cancelled'=>['#7f1d1d','#fca5a5']]; $c=$colors[$o->status]??['#1e2130','#94a3b8']; @endphp
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $c[0] }};color:{{ $c[1] }}">{{ ucfirst($o->status) }}</span>
        </td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:3px">
                <a href="{{ route('online-orders.show',$o) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                @if($o->status === 'new')
                <form method="POST" action="{{ route('online-orders.status',$o->id) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="processing">
                    <button type="submit" style="width:26px;height:26px;background:#14532d;border:.5px solid #166534;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#4ade80;cursor:pointer"><i class="ti ti-check" style="font-size:12px"></i></button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:32px;text-align:center;color:#4a5568">No online orders</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $orders->links() }}</div>
</div>
@endsection
