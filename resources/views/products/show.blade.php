{{-- products/show.blade.php --}}
@extends('layouts.app')
@section('title','Product')
@section('page-title','Product details')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('products.index') }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrow-left" style="font-size:12px"></i>Back</a>
    <a href="{{ route('products.edit',$product) }}" style="height:32px;padding:0 12px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i>Edit</a>
    <a href="{{ route('products.barcode',$product) }}" style="height:32px;padding:0 12px;background:var(--info-soft);color:var(--info);border:.5px solid var(--info-soft);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-barcode" style="font-size:12px"></i>Print barcode</a>
</div>
<div style="display:grid;grid-template-columns:260px 1fr;gap:12px">
<div>
    @if($product->image)
    <img src="{{ $product->imageUrl() }}" style="width:100%;border-radius:8px;margin-bottom:10px">
    @else
    <div style="width:100%;height:180px;background:var(--surface);border:.5px solid var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:10px"><i class="ti ti-package" style="color:var(--primary);font-size:40px"></i></div>
    @endif
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:12px">
        @foreach([['Name',$product->name,'var(--text)'],['Barcode',$product->barcode??'—','var(--text-3)'],['Category',$product->category?->name??'—','var(--info)'],['Brand',$product->brand?->name??'—','var(--text-2)'],['Unit',$product->unit,'var(--text-2)'],['Buy price','Rs. '.number_format($product->purchase_price),'var(--text-2)'],['Sale price','Rs. '.number_format($product->sale_price),'var(--primary-text)'],['Profit margin',$product->profitMargin().'%','var(--success)'],['Current stock',$product->current_stock.' units',$product->current_stock<=$product->min_stock?'var(--danger)':'var(--text)'],['Status',ucfirst($product->status),$product->status==='active'?'var(--success)':'var(--text-2)']] as [$l,$v,$c])
        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:.5px solid var(--surface-3);font-size:12px">
            <span style="color:var(--text-3)">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span>
        </div>
        @endforeach
    </div>
</div>
<div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Recent sales history</div>
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr style="border-bottom:.5px solid var(--border)">
                <th style="padding:6px 10px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Invoice</th>
                <th style="padding:6px 10px;color:var(--text-3);font-weight:500;font-size:11px">Date</th>
                <th style="padding:6px 10px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Qty</th>
                <th style="padding:6px 10px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Revenue</th>
            </tr></thead>
            <tbody>
            @forelse($salesHistory as $si)
            <tr style="border-bottom:.5px solid var(--surface-3)">
                <td style="padding:7px 10px;color:var(--primary)">{{ $si->sale?->invoice_no }}</td>
                <td style="padding:7px 10px;color:var(--text-3)">{{ $si->created_at->format('d M Y') }}</td>
                <td style="padding:7px 10px;text-align:right;color:var(--text-2)">{{ $si->quantity }}</td>
                <td style="padding:7px 10px;text-align:right;color:var(--success);font-weight:500">Rs. {{ number_format($si->subtotal) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="padding:20px;text-align:center;color:var(--text-4)">No sales history</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
@endsection
