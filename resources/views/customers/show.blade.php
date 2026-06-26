{{-- customers/show.blade.php --}}
@extends('layouts.app')
@section('title','Customer — '.$customer->name)
@section('page-title','Customer — '.$customer->name)
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('customers.index') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrow-left" style="font-size:12px"></i>Back</a>
    <a href="{{ route('customers.edit',$customer) }}" style="height:32px;padding:0 12px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i>Edit</a>
</div>
<div style="display:grid;grid-template-columns:300px 1fr;gap:12px">
<div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;text-align:center;margin-bottom:10px">
    <div style="width:60px;height:60px;background:#1e3a5f;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:500;color:#60a5fa;margin:0 auto 10px">{{ strtoupper(substr($customer->name,0,2)) }}</div>
    <div style="font-size:15px;font-weight:500;color:#e2e8f0">{{ $customer->name }}</div>
    <div style="font-size:12px;color:#64748b;margin-top:3px">{{ $customer->phone }}</div>
    @php $lvl=['bronze'=>['#7f1d1d','#fca5a5'],'silver'=>['#1e3a5f','#60a5fa'],'gold'=>['#451a03','#fb923c'],'platinum'=>['#312e81','#a5b4fc']][$customer->loyalty_level??'bronze']??['#1e2130','#94a3b8']; @endphp
    <span style="font-size:11px;padding:3px 12px;border-radius:10px;background:{{ $lvl[0] }};color:{{ $lvl[1] }};display:inline-block;margin-top:8px">{{ ucfirst($customer->loyalty_level ?? 'bronze') }}</span>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Summary</div>
    @foreach([['Total purchases','Rs. '.number_format($customer->total_purchases),'#4ade80'],['Loyalty points',number_format($customer->loyalty_points).' pts','#fb923c'],['Email',$customer->email??'—','#94a3b8'],['Address',$customer->address??'—','#94a3b8']] as [$l,$v,$c])
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <span style="color:#64748b">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span>
    </div>
    @endforeach
</div>
</div>
<div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Purchase history</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:6px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Invoice</th>
            <th style="padding:6px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Date</th>
            <th style="padding:6px 10px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Total</th>
            <th style="padding:6px 10px;text-align:center;color:#64748b;font-weight:500;font-size:11px">Status</th>
        </tr></thead>
        <tbody>
        @forelse($customer->sales as $sale)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px 10px;color:#818cf8">{{ $sale->invoice_no }}</td>
            <td style="padding:7px 10px;color:#64748b">{{ $sale->created_at->format('d M Y') }}</td>
            <td style="padding:7px 10px;text-align:right;color:#e2e8f0;font-weight:500">Rs. {{ number_format($sale->total) }}</td>
            <td style="padding:7px 10px;text-align:center">
                <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $sale->status==='paid'?'#14532d':'#451a03' }};color:{{ $sale->status==='paid'?'#4ade80':'#fb923c' }}">{{ ucfirst($sale->status) }}</span>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:20px;text-align:center;color:#4a5568">No purchases yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
</div>
</div>
@endsection
