{{-- purchases/show.blade.php --}}
@extends('layouts.app')
@section('title','Purchase #'.$purchase->bill_no)
@section('page-title','Purchase — '.$purchase->bill_no)
@section('content')
<div style="padding:14px 16px;max-width:900px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('purchases.index') }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-left" style="font-size:12px"></i>Back
    </a>
    <a href="{{ route('purchases.bill',$purchase->id) }}" style="height:32px;padding:0 12px;background:var(--info-soft);color:var(--info);border:.5px solid var(--info-soft);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-file-invoice" style="font-size:12px"></i>Print Bill
    </a>
    <a href="{{ route('purchase-returns.create') }}?purchase_id={{ $purchase->id }}" style="height:32px;padding:0 12px;background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-back-up" style="font-size:12px"></i>Return
    </a>
    <a href="{{ route('purchases.edit',$purchase) }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-edit" style="font-size:12px"></i>Edit
    </a>
    <form method="POST" action="{{ route('purchases.destroy',$purchase) }}" onsubmit="return confirm('Delete this purchase and reverse its stock? This cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--danger);font-size:12px;display:flex;align-items:center;gap:4px;cursor:pointer">
            <i class="ti ti-trash" style="font-size:12px"></i>Delete
        </button>
    </form>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:12px">
<div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Purchase items</div>
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr style="border-bottom:.5px solid var(--border)">
                <th style="padding:6px 0;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Product</th>
                <th style="padding:6px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Batch</th>
                <th style="padding:6px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">Qty</th>
                <th style="padding:6px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Unit price</th>
                <th style="padding:6px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">MRP</th>
                <th style="padding:6px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Sale price</th>
                <th style="padding:6px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Subtotal</th>
            </tr></thead>
            <tbody>
            @foreach($purchase->items as $item)
            <tr style="border-bottom:.5px solid var(--surface-3)">
                <td style="padding:8px 0;color:var(--text)">{{ $item->product?->name ?? $item->name }}@unless($item->product_id)<span style="font-size:9px;color:var(--text-3);margin-left:4px">custom</span>@endunless</td>
                <td style="padding:8px 6px;color:var(--text-3)">{{ $item->batch_no ?: '—' }}</td>
                <td style="padding:8px 6px;text-align:center;color:var(--text-2)">{{ $item->quantity }}</td>
                <td style="padding:8px 6px;text-align:right;color:var(--text-2)">Rs. {{ number_format($item->unit_price) }}</td>
                <td style="padding:8px 6px;text-align:right;color:var(--text-3)">{{ $item->mrp ? 'Rs. '.number_format($item->mrp) : '—' }}</td>
                <td style="padding:8px 6px;text-align:right;color:var(--text-2)">{{ $item->sale_price ? 'Rs. '.number_format($item->sale_price) : '—' }}</td>
                <td style="padding:8px 6px;text-align:right;color:var(--text);font-weight:500">Rs. {{ number_format($item->subtotal) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Payment history</div>
        @forelse($purchase->payments as $pay)
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:.5px solid var(--surface-3);font-size:12px">
            <div style="color:var(--text-2)">{{ $pay->created_at->format('d M Y H:i') }} — {{ ucfirst(str_replace('_',' ',$pay->method)) }}</div>
            <div style="color:var(--success);font-weight:500">Rs. {{ number_format($pay->amount) }}</div>
        </div>
        @empty
        <div style="color:var(--text-3);font-size:12px">No payments recorded</div>
        @endforelse

        @if($purchase->balance_due > 0)
        <form method="POST" action="{{ route('payments.out.store') }}" style="display:flex;gap:8px;margin-top:10px">
            @csrf
            <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
            <input type="number" name="amount" placeholder="Amount" max="{{ $purchase->balance_due }}" step="0.01"
                style="flex:1;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:6px 10px;outline:none">
            <select name="account_id" style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:6px 8px;outline:none">
                @foreach(\App\Models\Account::whereBranch(\App\Support\CurrentBranch::id())->get() as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                @endforeach
            </select>
            <button type="submit" style="height:34px;padding:0 12px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;cursor:pointer">Pay</button>
        </form>
        @endif
    </div>
</div>

<div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Summary</div>
        @foreach([
            ['Bill No.',$purchase->bill_no,'var(--info)'],
            ['Date',\Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y'),'var(--text)'],
            ['Due date',$purchase->due_date ? \Carbon\Carbon::parse($purchase->due_date)->format('d M Y') : '—','var(--text)'],
            ['Payment',$purchase->payment_method,'var(--text)'],
        ] as [$l,$v,$c])
        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:.5px solid var(--surface-3);font-size:12px">
            <span style="color:var(--text-3)">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span>
        </div>
        @endforeach
        <div style="margin-top:10px;padding-top:8px;border-top:.5px solid var(--border)">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-2);margin-bottom:3px"><span>Subtotal</span><span>Rs. {{ number_format($purchase->subtotal) }}</span></div>
            @if($purchase->discount_amount > 0)
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--success);margin-bottom:3px"><span>Discount</span><span>- Rs. {{ number_format($purchase->discount_amount) }}</span></div>
            @endif
            <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:500;color:var(--text);margin-top:6px;padding-top:6px;border-top:.5px solid var(--border)"><span>Total</span><span>Rs. {{ number_format($purchase->total) }}</span></div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--success);margin-top:3px"><span>Paid</span><span>Rs. {{ number_format($purchase->paid_amount) }}</span></div>
            @if($purchase->balance_due > 0)
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--danger);margin-top:3px"><span>Balance due</span><span>Rs. {{ number_format($purchase->balance_due) }}</span></div>
            @endif
        </div>
        <div style="margin-top:10px;text-align:center">
            <span style="font-size:11px;padding:4px 14px;border-radius:10px;font-weight:500;
                background:{{ ['paid'=>'var(--success-soft)','partial'=>'var(--warning-soft)','unpaid'=>'var(--danger-soft)'][$purchase->payment_status]??'var(--surface-2)' }};
                color:{{ ['paid'=>'var(--success)','partial'=>'var(--warning)','unpaid'=>'var(--danger-text)'][$purchase->payment_status]??'var(--text-2)' }}">
                {{ strtoupper($purchase->payment_status) }}
            </span>
        </div>
    </div>

    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Supplier</div>
        <div style="font-size:13px;color:var(--text);font-weight:500">{{ $purchase->supplier?->name }}</div>
        <div style="font-size:12px;color:var(--text-3);margin-top:3px">{{ $purchase->supplier?->contact_person }}</div>
        <div style="font-size:12px;color:var(--text-3);margin-top:2px">{{ $purchase->supplier?->phone }}</div>
    </div>
</div>
</div>
</div>
@endsection
