{{-- purchase-returns/show.blade.php --}}
@extends('layouts.app')
@section('title','Dr. Note '.$purchaseReturn->dr_note_no)
@section('page-title','Purchase Return — '.$purchaseReturn->dr_note_no)
@section('content')
<div style="padding:14px 16px;max-width:820px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('purchase-returns.index') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-left" style="font-size:12px"></i>Back
    </a>
    @if($purchaseReturn->purchase)
    <a href="{{ route('purchases.show',$purchaseReturn->purchase) }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#60a5fa;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-file-invoice" style="font-size:12px"></i>Bill {{ $purchaseReturn->purchase->bill_no }}
    </a>
    @endif
    <form method="POST" action="{{ route('purchase-returns.destroy',$purchaseReturn) }}" onsubmit="return confirm('Reverse this Dr. Note? Stock and any credit/refund will be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#f87171;font-size:12px;display:flex;align-items:center;gap:4px;cursor:pointer">
            <i class="ti ti-arrow-back-up" style="font-size:12px"></i>Reverse return
        </button>
    </form>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:12px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Returned items</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:6px 0;text-align:left;color:#64748b;font-weight:500;font-size:11px">Product</th>
            <th style="padding:6px;text-align:center;color:#64748b;font-weight:500;font-size:11px">Qty</th>
            <th style="padding:6px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Unit price</th>
            <th style="padding:6px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Subtotal</th>
        </tr></thead>
        <tbody>
        @forelse($purchaseReturn->items as $item)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:8px 0;color:#e2e8f0">{{ $item->product?->name ?? '—' }}</td>
            <td style="padding:8px 6px;text-align:center;color:#94a3b8">{{ rtrim(rtrim(number_format($item->quantity,3),'0'),'.') }}</td>
            <td style="padding:8px 6px;text-align:right;color:#94a3b8">Rs. {{ number_format($item->unit_price,2) }}</td>
            <td style="padding:8px 6px;text-align:right;color:#e2e8f0;font-weight:500">Rs. {{ number_format($item->subtotal,2) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:16px;text-align:center;color:#4a5568">No line items recorded</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Summary</div>
        @foreach([
            ['Dr. Note',$purchaseReturn->dr_note_no,'#f87171'],
            ['Bill',$purchaseReturn->purchase?->bill_no ?? '—','#60a5fa'],
            ['Supplier',$purchaseReturn->purchase?->supplier?->name ?? $purchaseReturn->supplier?->name ?? '—','#e2e8f0'],
            ['Date',$purchaseReturn->created_at->format('d M Y H:i'),'#e2e8f0'],
            ['Credit method',ucfirst(str_replace('_',' ',$purchaseReturn->credit_method)),'#e2e8f0'],
            ['Status',ucfirst($purchaseReturn->status),$purchaseReturn->status==='credited'?'#4ade80':'#fb923c'],
        ] as [$l,$v,$c])
        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
            <span style="color:#64748b">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span>
        </div>
        @endforeach
        <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:500;color:#f87171;margin-top:10px;padding-top:8px;border-top:.5px solid #2a2d3a">
            <span>Total credit</span><span>Rs. {{ number_format($purchaseReturn->return_amount,2) }}</span>
        </div>
    </div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:6px">Reason</div>
        <div style="font-size:12px;color:#e2e8f0">{{ $purchaseReturn->reason }}</div>
    </div>
</div>
</div>
</div>
@endsection
