{{-- sales/show.blade.php --}}
@extends('layouts.app')
@section('title','Sale #'.$sale->invoice_no)
@section('page-title','Sale — '.$sale->invoice_no)

@section('content')
<div style="padding:14px 16px;max-width:900px">

{{-- Header actions --}}
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('sales.index') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-left" style="font-size:12px"></i>Back
    </a>
    <a href="{{ route('sales.receipt',$sale->id) }}" target="_blank" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-printer" style="font-size:12px"></i>Print receipt
    </a>
    <a href="{{ route('sales.invoice',$sale->id) }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#60a5fa;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-file-invoice" style="font-size:12px"></i>PDF Invoice
    </a>
    @if($sale->status !== 'returned')
    <a href="{{ route('sale-returns.create') }}?sale_id={{ $sale->id }}" style="height:32px;padding:0 12px;background:#7f1d1d;color:#fca5a5;border:.5px solid #991b1b;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-back-up" style="font-size:12px"></i>Return
    </a>
    @endif
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:12px">

{{-- Left: Items --}}
<div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Invoice items</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:6px 0;text-align:left;color:#64748b;font-weight:500;font-size:11px">Product</th>
            <th style="padding:6px;text-align:center;color:#64748b;font-weight:500;font-size:11px">Qty</th>
            <th style="padding:6px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Price</th>
            <th style="padding:6px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Total</th>
        </tr></thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr style="border-bottom:.5px solid #1a1d2a">
                <td style="padding:8px 0;color:#e2e8f0">{{ $item->product?->name ?? $item->name }}</td>
                <td style="padding:8px 6px;text-align:center;color:#94a3b8">{{ $item->quantity }}</td>
                <td style="padding:8px 6px;text-align:right;color:#94a3b8">Rs. {{ number_format($item->unit_price) }}</td>
                <td style="padding:8px 6px;text-align:right;color:#e2e8f0;font-weight:500">Rs. {{ number_format($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Payment history</div>
    @forelse($sale->payments as $pay)
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <div style="color:#94a3b8">{{ $pay->created_at->format('d M H:i') }} — {{ ucfirst($pay->method) }}</div>
        <div style="color:#4ade80;font-weight:500">Rs. {{ number_format($pay->amount) }}</div>
    </div>
    @empty
    <div style="color:#64748b;font-size:12px">No payment records</div>
    @endforelse
    @if($sale->status === 'partial')
    <div style="margin-top:10px">
        <form method="POST" action="{{ route('payments.in.store') }}" style="display:flex;gap:8px">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $sale->id }}">
            <input type="number" name="amount" placeholder="Amount" max="{{ $sale->total - $sale->paid_amount }}"
                style="flex:1;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:6px 10px;outline:none">
            <select name="account_id" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:6px 8px;outline:none">
                @foreach(\App\Models\Account::where('branch_id',auth()->user()->branch_id)->get() as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                @endforeach
            </select>
            <button type="submit" style="height:34px;padding:0 12px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;cursor:pointer">Collect</button>
        </form>
    </div>
    @endif
</div>
</div>

{{-- Right: Summary --}}
<div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Sale summary</div>
    @foreach([
        ['Invoice No.',$sale->invoice_no,'#a5b4fc'],
        ['Date',$sale->created_at->format('d M Y H:i'),'#e2e8f0'],
        ['Cashier',$sale->user?->name ?? '—','#e2e8f0'],
        ['Payment',$sale->payment_method,'#e2e8f0'],
    ] as [$lbl,$val,$col])
    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <span style="color:#64748b">{{ $lbl }}</span><span style="color:{{ $col }};font-weight:500">{{ $val }}</span>
    </div>
    @endforeach
    <div style="margin-top:10px;padding-top:8px;border-top:.5px solid #2a2d3a">
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-bottom:3px"><span>Subtotal</span><span>Rs. {{ number_format($sale->subtotal) }}</span></div>
        @if($sale->discount_amount > 0)
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#4ade80;margin-bottom:3px"><span>Discount</span><span>- Rs. {{ number_format($sale->discount_amount) }}</span></div>
        @endif
        @if($sale->tax_amount > 0)
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-bottom:3px"><span>Tax</span><span>Rs. {{ number_format($sale->tax_amount) }}</span></div>
        @endif
        <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:500;color:#e2e8f0;margin-top:6px;padding-top:6px;border-top:.5px solid #2a2d3a"><span>Total</span><span>Rs. {{ number_format($sale->total) }}</span></div>
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#4ade80;margin-top:3px"><span>Paid</span><span>Rs. {{ number_format($sale->paid_amount) }}</span></div>
        @if($sale->balanceDue() > 0)
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#f87171;margin-top:3px"><span>Balance due</span><span>Rs. {{ number_format($sale->balanceDue()) }}</span></div>
        @endif
    </div>
    <div style="margin-top:10px;text-align:center">
        <span style="font-size:11px;padding:4px 14px;border-radius:10px;font-weight:500;
            background:{{ ['paid'=>'#14532d','partial'=>'#451a03','returned'=>'#7f1d1d'][$sale->status] ?? '#1e2130' }};
            color:{{ ['paid'=>'#4ade80','partial'=>'#fb923c','returned'=>'#fca5a5'][$sale->status] ?? '#94a3b8' }}">
            {{ strtoupper($sale->status) }}
        </span>
    </div>
</div>

@if($sale->customer)
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Customer</div>
    <div style="font-size:13px;color:#e2e8f0;font-weight:500">{{ $sale->customer->name }}</div>
    <div style="font-size:12px;color:#64748b;margin-top:3px">{{ $sale->customer->phone }}</div>
    <div style="font-size:12px;color:#fb923c;margin-top:3px"><i class="ti ti-star" style="font-size:11px"></i> {{ $sale->customer->loyalty_points }} loyalty pts</div>
</div>
@endif
</div>

</div>
</div>
@endsection
