{{-- products/show.blade.php --}}
@extends('layouts.app')
@section('title','Product')
@section('page-title','Product details')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('products.index') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrow-left" style="font-size:12px"></i>Back</a>
    <a href="{{ route('products.edit',$product) }}" style="height:32px;padding:0 12px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i>Edit</a>
    <a href="{{ route('products.barcode',$product) }}" style="height:32px;padding:0 12px;background:#1e3a5f;color:#60a5fa;border:.5px solid #1e3a5f;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-barcode" style="font-size:12px"></i>Print barcode</a>
</div>
<div style="display:grid;grid-template-columns:260px 1fr;gap:12px">
<div>
    @if($product->image)
    <img src="{{ asset('storage/'.$product->image) }}" style="width:100%;border-radius:8px;margin-bottom:10px">
    @else
    <div style="width:100%;height:180px;background:#161821;border:.5px solid #2a2d3a;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:10px"><i class="ti ti-package" style="color:#818cf8;font-size:40px"></i></div>
    @endif
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px">
        @foreach([['Name',$product->name,'#e2e8f0'],['Barcode',$product->barcode??'—','#64748b'],['Category',$product->category?->name??'—','#60a5fa'],['Brand',$product->brand?->name??'—','#94a3b8'],['Unit',$product->unit,'#94a3b8'],['Buy price','Rs. '.number_format($product->purchase_price),'#94a3b8'],['Sale price','Rs. '.number_format($product->sale_price),'#a5b4fc'],['Profit margin',$product->profitMargin().'%','#4ade80'],['Current stock',$product->current_stock.' units',$product->current_stock<=$product->min_stock?'#f87171':'#e2e8f0'],['Status',ucfirst($product->status),$product->status==='active'?'#4ade80':'#94a3b8']] as [$l,$v,$c])
        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
            <span style="color:#64748b">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span>
        </div>
        @endforeach
    </div>
</div>
<div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Recent sales history</div>
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr style="border-bottom:.5px solid #2a2d3a">
                <th style="padding:6px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Invoice</th>
                <th style="padding:6px 10px;color:#64748b;font-weight:500;font-size:11px">Date</th>
                <th style="padding:6px 10px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Qty</th>
                <th style="padding:6px 10px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Revenue</th>
            </tr></thead>
            <tbody>
            @forelse($salesHistory as $si)
            <tr style="border-bottom:.5px solid #1a1d2a">
                <td style="padding:7px 10px;color:#818cf8">{{ $si->sale?->invoice_no }}</td>
                <td style="padding:7px 10px;color:#64748b">{{ $si->created_at->format('d M Y') }}</td>
                <td style="padding:7px 10px;text-align:right;color:#94a3b8">{{ $si->quantity }}</td>
                <td style="padding:7px 10px;text-align:right;color:#4ade80;font-weight:500">Rs. {{ number_format($si->subtotal) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="padding:20px;text-align:center;color:#4a5568">No sales history</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
@endsection
