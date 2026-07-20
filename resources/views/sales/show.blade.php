{{-- sales/show.blade.php --}}
@extends('layouts.app')
@section('title','Sale #'.$sale->invoice_no)
@section('page-title','Sale — '.$sale->invoice_no)

@section('content')
<div style="padding:14px 16px;max-width:900px">

{{-- Header actions --}}
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('sales.index') }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-left" style="font-size:12px"></i>Back
    </a>
    <a href="{{ route('sales.receipt',$sale->id) }}" target="_blank" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-printer" style="font-size:12px"></i>Print receipt
    </a>
    <a href="{{ route('sales.invoice',$sale->id) }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--info);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-file-invoice" style="font-size:12px"></i>PDF Invoice
    </a>
    @if($sale->status !== 'returned')
    <a href="{{ route('sale-returns.create') }}?sale_id={{ $sale->id }}" style="height:32px;padding:0 12px;background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-back-up" style="font-size:12px"></i>Return
    </a>
    <form method="POST" action="{{ route('sales.destroy',$sale) }}" onsubmit="return confirm('Void invoice {{ $sale->invoice_no }}? Stock and payments will be reversed. This cannot be undone.');" style="margin-left:auto">
        @csrf
        @method('DELETE')
        <button type="submit" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--danger);font-size:12px;display:flex;align-items:center;gap:4px;cursor:pointer">
            <i class="ti ti-trash" style="font-size:12px"></i>Void sale
        </button>
    </form>
    @endif
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:12px">

{{-- Left: Items --}}
<div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Invoice items</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            <th style="padding:6px 0;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Product</th>
            <th style="padding:6px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">Qty</th>
            <th style="padding:6px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Price</th>
            <th style="padding:6px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Total</th>
        </tr></thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr style="border-bottom:.5px solid var(--surface-3)">
                <td style="padding:8px 0;color:var(--text)">{{ $item->product?->name ?? $item->name }}</td>
                <td style="padding:8px 6px;text-align:center;color:var(--text-2)">{{ $item->quantity }}</td>
                <td style="padding:8px 6px;text-align:right;color:var(--text-2)">Rs. {{ number_format($item->unit_price) }}</td>
                <td style="padding:8px 6px;text-align:right;color:var(--text);font-weight:500">Rs. {{ number_format($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Payment history</div>
    @forelse($sale->payments as $pay)
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:.5px solid var(--surface-3);font-size:12px">
        <div style="color:var(--text-2)">{{ $pay->created_at->format('d M H:i') }} — {{ ucfirst($pay->method) }}</div>
        <div style="color:var(--success);font-weight:500">Rs. {{ number_format($pay->amount) }}</div>
    </div>
    @empty
    <div style="color:var(--text-3);font-size:12px">No payment records</div>
    @endforelse
    @if($sale->status === 'partial')
    <div style="margin-top:10px">
        <form method="POST" action="{{ route('payments.in.store') }}" style="display:flex;gap:8px">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $sale->id }}">
            <input type="number" name="amount" placeholder="Amount" max="{{ $sale->total - $sale->paid_amount }}"
                style="flex:1;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:6px 10px;outline:none">
            <select name="account_id" style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:6px 8px;outline:none">
                @foreach(\App\Models\Account::whereBranch(\App\Support\CurrentBranch::id())->get() as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                @endforeach
            </select>
            <button type="submit" style="height:34px;padding:0 12px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;cursor:pointer">Collect</button>
        </form>
    </div>
    @endif
</div>
</div>

{{-- Right: Summary --}}
<div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Sale summary</div>
    @foreach([
        ['Invoice No.',$sale->invoice_no,'var(--primary-text)'],
        ['Date',$sale->created_at->format('d M Y H:i'),'var(--text)'],
        ['Cashier',$sale->user?->name ?? '—','var(--text)'],
        ['Payment',$sale->payment_method,'var(--text)'],
    ] as [$lbl,$val,$col])
    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:.5px solid var(--surface-3);font-size:12px">
        <span style="color:var(--text-3)">{{ $lbl }}</span><span style="color:{{ $col }};font-weight:500">{{ $val }}</span>
    </div>
    @endforeach
    <div style="margin-top:10px;padding-top:8px;border-top:.5px solid var(--border)">
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-2);margin-bottom:3px"><span>Subtotal</span><span>Rs. {{ number_format($sale->subtotal) }}</span></div>
        @if($sale->discount_amount > 0)
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--success);margin-bottom:3px"><span>Discount</span><span>- Rs. {{ number_format($sale->discount_amount) }}</span></div>
        @endif
        @if($sale->tax_amount > 0)
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-2);margin-bottom:3px"><span>Tax</span><span>Rs. {{ number_format($sale->tax_amount) }}</span></div>
        @endif
        <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:500;color:var(--text);margin-top:6px;padding-top:6px;border-top:.5px solid var(--border)"><span>Total</span><span>Rs. {{ number_format($sale->total) }}</span></div>
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--success);margin-top:3px"><span>Paid</span><span>Rs. {{ number_format($sale->paid_amount) }}</span></div>
        @if($sale->balanceDue() > 0)
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--danger);margin-top:3px"><span>Balance due</span><span>Rs. {{ number_format($sale->balanceDue()) }}</span></div>
        @endif
    </div>
    <div style="margin-top:10px;text-align:center">
        <span style="font-size:11px;padding:4px 14px;border-radius:10px;font-weight:500;
            background:{{ ['paid'=>'var(--success-soft)','partial'=>'var(--warning-soft)','returned'=>'var(--danger-soft)'][$sale->status] ?? 'var(--surface-2)' }};
            color:{{ ['paid'=>'var(--success)','partial'=>'var(--warning)','returned'=>'var(--danger-text)'][$sale->status] ?? 'var(--text-2)' }}">
            {{ strtoupper($sale->status) }}
        </span>
    </div>
</div>

@if($sale->customer)
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Customer</div>
    <div style="font-size:13px;color:var(--text);font-weight:500">{{ $sale->customer->name }}</div>
    <div style="font-size:12px;color:var(--text-3);margin-top:3px">{{ $sale->customer->phone }}</div>
    <div style="font-size:12px;color:var(--warning);margin-top:3px"><i class="ti ti-star" style="font-size:11px"></i> {{ $sale->customer->loyalty_points }} loyalty pts</div>
</div>
@endif
</div>

</div>
</div>
@endsection
