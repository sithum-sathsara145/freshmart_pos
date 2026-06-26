{{-- quotations/show.blade.php --}}
@extends('layouts.app')
@section('title','Quote #'.$quotation->quote_no)
@section('page-title','Quotation — '.$quotation->quote_no)
@section('content')
<div style="padding:14px 16px;max-width:800px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('quotations.index') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-left" style="font-size:12px"></i>Back
    </a>
    <a href="{{ route('quotations.pdf',$quotation->id) }}" style="height:32px;padding:0 12px;background:#1e3a5f;color:#60a5fa;border:.5px solid #1e3a5f;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-file-invoice" style="font-size:12px"></i>PDF
    </a>
    @if($quotation->status === 'pending')
    <form method="POST" action="{{ route('quotations.convert',$quotation->id) }}">
        @csrf
        <button type="submit" style="height:32px;padding:0 14px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">
            <i class="ti ti-arrow-right" style="font-size:12px;margin-right:4px"></i>Convert to Sale
        </button>
    </form>
    @endif
</div>

<div style="display:grid;grid-template-columns:1fr 260px;gap:12px">
<div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Items</div>
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr style="border-bottom:.5px solid #2a2d3a">
                <th style="padding:6px 0;text-align:left;color:#64748b;font-weight:500;font-size:11px">Product</th>
                <th style="padding:6px;text-align:center;color:#64748b;font-weight:500;font-size:11px">Qty</th>
                <th style="padding:6px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Price</th>
                <th style="padding:6px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Total</th>
            </tr></thead>
            <tbody>
            @foreach($quotation->items as $item)
            <tr style="border-bottom:.5px solid #1a1d2a">
                <td style="padding:8px 0;color:#e2e8f0">{{ $item->product?->name ?? $item->product_name }}</td>
                <td style="padding:8px 6px;text-align:center;color:#94a3b8">{{ $item->quantity }}</td>
                <td style="padding:8px 6px;text-align:right;color:#94a3b8">Rs. {{ number_format($item->unit_price) }}</td>
                <td style="padding:8px 6px;text-align:right;color:#e2e8f0;font-weight:500">Rs. {{ number_format($item->subtotal) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($quotation->notes)
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px;font-size:12px;color:#94a3b8">
        <i class="ti ti-info-circle" style="font-size:13px;margin-right:4px;color:#64748b"></i>{{ $quotation->notes }}
    </div>
    @endif
</div>
<div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Summary</div>
        @foreach([['Quote #',$quotation->quote_no,'#60a5fa'],['Date',$quotation->created_at->format('d M Y'),'#64748b'],['Valid till',$quotation->valid_till?\Carbon\Carbon::parse($quotation->valid_till)->format('d M Y'):'—','#64748b'],['Customer',$quotation->customer?->name??'Walk-in','#e2e8f0']] as [$l,$v,$c])
        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
            <span style="color:#64748b">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span>
        </div>
        @endforeach
        <div style="margin-top:10px;padding-top:8px;border-top:.5px solid #2a2d3a">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-bottom:3px">
                <span>Subtotal</span><span>Rs. {{ number_format($quotation->subtotal) }}</span>
            </div>
            @if($quotation->discount_amount > 0)
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#4ade80;margin-bottom:3px">
                <span>Discount</span><span>- Rs. {{ number_format($quotation->discount_amount) }}</span>
            </div>
            @endif
            <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:500;color:#e2e8f0;margin-top:6px;padding-top:6px;border-top:.5px solid #2a2d3a">
                <span>Total</span><span>Rs. {{ number_format($quotation->total) }}</span>
            </div>
        </div>
        @php $colors=['pending'=>['#451a03','#fb923c'],'converted'=>['#14532d','#4ade80'],'expired'=>['#1e2130','#94a3b8']]; $c=$colors[$quotation->status]??['#1e2130','#94a3b8']; @endphp
        <div style="margin-top:10px;text-align:center">
            <span style="font-size:11px;padding:4px 14px;border-radius:10px;font-weight:500;background:{{ $c[0] }};color:{{ $c[1] }}">
                {{ strtoupper($quotation->status) }}
            </span>
        </div>
    </div>
</div>
</div>
</div>
@endsection
