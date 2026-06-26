{{-- purchases/show.blade.php --}}
@extends('layouts.app')
@section('title','Purchase #'.$purchase->bill_no)
@section('page-title','Purchase — '.$purchase->bill_no)
@section('content')
<div style="padding:14px 16px;max-width:900px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('purchases.index') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-left" style="font-size:12px"></i>Back
    </a>
    <a href="{{ route('purchases.bill',$purchase->id) }}" style="height:32px;padding:0 12px;background:#1e3a5f;color:#60a5fa;border:.5px solid #1e3a5f;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-file-invoice" style="font-size:12px"></i>Print Bill
    </a>
    <a href="{{ route('purchase-returns.create') }}?purchase_id={{ $purchase->id }}" style="height:32px;padding:0 12px;background:#7f1d1d;color:#fca5a5;border:.5px solid #991b1b;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-back-up" style="font-size:12px"></i>Return
    </a>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:12px">
<div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Purchase items</div>
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr style="border-bottom:.5px solid #2a2d3a">
                <th style="padding:6px 0;text-align:left;color:#64748b;font-weight:500;font-size:11px">Product</th>
                <th style="padding:6px;text-align:center;color:#64748b;font-weight:500;font-size:11px">Qty</th>
                <th style="padding:6px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Unit price</th>
                <th style="padding:6px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Subtotal</th>
            </tr></thead>
            <tbody>
            @foreach($purchase->items as $item)
            <tr style="border-bottom:.5px solid #1a1d2a">
                <td style="padding:8px 0;color:#e2e8f0">{{ $item->product?->name }}</td>
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
        @forelse($purchase->payments as $pay)
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
            <div style="color:#94a3b8">{{ $pay->created_at->format('d M Y H:i') }} — {{ ucfirst(str_replace('_',' ',$pay->method)) }}</div>
            <div style="color:#4ade80;font-weight:500">Rs. {{ number_format($pay->amount) }}</div>
        </div>
        @empty
        <div style="color:#64748b;font-size:12px">No payments recorded</div>
        @endforelse

        @if($purchase->balance_due > 0)
        <form method="POST" action="{{ route('payments.out.store') }}" style="display:flex;gap:8px;margin-top:10px">
            @csrf
            <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
            <input type="number" name="amount" placeholder="Amount" max="{{ $purchase->balance_due }}" step="0.01"
                style="flex:1;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:6px 10px;outline:none">
            <select name="account_id" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:6px 8px;outline:none">
                @foreach(\App\Models\Account::where('branch_id',auth()->user()->branch_id)->get() as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                @endforeach
            </select>
            <button type="submit" style="height:34px;padding:0 12px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;cursor:pointer">Pay</button>
        </form>
        @endif
    </div>
</div>

<div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Summary</div>
        @foreach([
            ['Bill No.',$purchase->bill_no,'#60a5fa'],
            ['Date',\Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y'),'#e2e8f0'],
            ['Due date',$purchase->due_date ? \Carbon\Carbon::parse($purchase->due_date)->format('d M Y') : '—','#e2e8f0'],
            ['Payment',$purchase->payment_method,'#e2e8f0'],
        ] as [$l,$v,$c])
        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
            <span style="color:#64748b">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span>
        </div>
        @endforeach
        <div style="margin-top:10px;padding-top:8px;border-top:.5px solid #2a2d3a">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-bottom:3px"><span>Subtotal</span><span>Rs. {{ number_format($purchase->subtotal) }}</span></div>
            @if($purchase->discount_amount > 0)
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#4ade80;margin-bottom:3px"><span>Discount</span><span>- Rs. {{ number_format($purchase->discount_amount) }}</span></div>
            @endif
            <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:500;color:#e2e8f0;margin-top:6px;padding-top:6px;border-top:.5px solid #2a2d3a"><span>Total</span><span>Rs. {{ number_format($purchase->total) }}</span></div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#4ade80;margin-top:3px"><span>Paid</span><span>Rs. {{ number_format($purchase->paid_amount) }}</span></div>
            @if($purchase->balance_due > 0)
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#f87171;margin-top:3px"><span>Balance due</span><span>Rs. {{ number_format($purchase->balance_due) }}</span></div>
            @endif
        </div>
        <div style="margin-top:10px;text-align:center">
            <span style="font-size:11px;padding:4px 14px;border-radius:10px;font-weight:500;
                background:{{ ['paid'=>'#14532d','partial'=>'#451a03','unpaid'=>'#7f1d1d'][$purchase->payment_status]??'#1e2130' }};
                color:{{ ['paid'=>'#4ade80','partial'=>'#fb923c','unpaid'=>'#fca5a5'][$purchase->payment_status]??'#94a3b8' }}">
                {{ strtoupper($purchase->payment_status) }}
            </span>
        </div>
    </div>

    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Supplier</div>
        <div style="font-size:13px;color:#e2e8f0;font-weight:500">{{ $purchase->supplier?->name }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:3px">{{ $purchase->supplier?->contact_person }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:2px">{{ $purchase->supplier?->phone }}</div>
    </div>
</div>
</div>
</div>
@endsection
