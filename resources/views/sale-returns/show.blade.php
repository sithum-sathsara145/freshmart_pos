{{-- sale-returns/show.blade.php --}}
@extends('layouts.app')
@section('title','Credit Note '.$saleReturn->credit_note_no)
@section('page-title','Sales Return — '.$saleReturn->credit_note_no)
@section('content')
<div style="padding:14px 16px;max-width:820px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('sale-returns.index') }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-left" style="font-size:12px"></i>Back
    </a>
    @if($saleReturn->sale)
    <a href="{{ route('sales.show',$saleReturn->sale) }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--info);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-file-invoice" style="font-size:12px"></i>Invoice {{ $saleReturn->sale->invoice_no }}
    </a>
    @endif
    <form method="POST" action="{{ route('sale-returns.destroy',$saleReturn) }}" onsubmit="return confirm('Reverse this return? Stock and any cash refund will be undone. This cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--danger);font-size:12px;display:flex;align-items:center;gap:4px;cursor:pointer">
            <i class="ti ti-arrow-back-up" style="font-size:12px"></i>Reverse return
        </button>
    </form>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:12px">
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Returned items</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            <th style="padding:6px 0;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Product</th>
            <th style="padding:6px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">Qty</th>
            <th style="padding:6px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Unit price</th>
            <th style="padding:6px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Subtotal</th>
        </tr></thead>
        <tbody>
        @foreach($saleReturn->items as $item)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:8px 0;color:var(--text)">{{ $item->product?->name ?? '—' }}</td>
            <td style="padding:8px 6px;text-align:center;color:var(--text-2)">{{ rtrim(rtrim(number_format($item->quantity,3),'0'),'.') }}</td>
            <td style="padding:8px 6px;text-align:right;color:var(--text-2)">Rs. {{ number_format($item->unit_price,2) }}</td>
            <td style="padding:8px 6px;text-align:right;color:var(--text);font-weight:500">Rs. {{ number_format($item->subtotal,2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Summary</div>
        @foreach([
            ['Cr. Note',$saleReturn->credit_note_no,'var(--danger)'],
            ['Invoice',$saleReturn->sale?->invoice_no ?? '—','var(--info)'],
            ['Customer',$saleReturn->customer?->name ?? $saleReturn->sale?->customer?->name ?? 'Walk-in','var(--text)'],
            ['Date',$saleReturn->created_at->format('d M Y H:i'),'var(--text)'],
            ['Refund',ucfirst(str_replace('_',' ',$saleReturn->refund_method)),'var(--text)'],
        ] as [$l,$v,$c])
        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:.5px solid var(--surface-3);font-size:12px">
            <span style="color:var(--text-3)">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span>
        </div>
        @endforeach
        <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:500;color:var(--danger);margin-top:10px;padding-top:8px;border-top:.5px solid var(--border)">
            <span>Total refund</span><span>Rs. {{ number_format($saleReturn->return_amount,2) }}</span>
        </div>
    </div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:6px">Reason</div>
        <div style="font-size:12px;color:var(--text)">{{ $saleReturn->reason }}</div>
    </div>
</div>
</div>
</div>
@endsection
