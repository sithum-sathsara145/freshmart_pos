{{-- customers/show.blade.php --}}
@extends('layouts.app')
@section('title','Customer — '.$customer->name)
@section('page-title','Customer — '.$customer->name)
@section('content')
<div style="padding:14px 16px" x-data="creditEvidence()">
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
{{-- Credit standing --}}
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-top:10px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px;display:flex;align-items:center;gap:6px"><i class="ti ti-credit-card" style="font-size:13px;color:#818cf8"></i>Credit</div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <span style="color:#64748b">Status</span>
        <span style="color:{{ $customer->credit_approved ? '#4ade80' : '#94a3b8' }};font-weight:500">{{ $customer->credit_approved ? 'Approved' : 'Not approved' }}</span>
    </div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <span style="color:#64748b">Credit limit</span>
        <span style="color:#94a3b8;font-weight:500">{{ $customer->credit_limit !== null ? 'Rs. '.number_format($customer->credit_limit) : 'No limit' }}</span>
    </div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:12px">
        <span style="color:#64748b">Outstanding</span>
        <span style="color:{{ $customer->outstandingBalance() > 0 ? '#fb923c' : '#4ade80' }};font-weight:600">Rs. {{ number_format($customer->outstandingBalance(),2) }}</span>
    </div>
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
{{-- Credit history — unpaid credit bills, with signed-copy status / upload --}}
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-top:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px;display:flex;align-items:center;gap:6px"><i class="ti ti-calendar-due" style="font-size:13px;color:#fb923c"></i>Credit history <span style="font-size:11px;color:#64748b">— all credit bills</span></div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:6px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Invoice</th>
            <th style="padding:6px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Date</th>
            <th style="padding:6px 10px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Total</th>
            <th style="padding:6px 10px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Balance due</th>
            <th style="padding:6px 10px;text-align:center;color:#64748b;font-weight:500;font-size:11px">Signed copy</th>
            <th style="padding:6px 10px;text-align:center;color:#64748b;font-weight:500;font-size:11px">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($creditSales as $sale)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px 10px"><a href="{{ route('sales.show',$sale) }}" style="color:#818cf8;text-decoration:none">{{ $sale->invoice_no }}</a></td>
            <td style="padding:7px 10px;color:#64748b">{{ $sale->created_at->format('d M Y') }}</td>
            <td style="padding:7px 10px;text-align:right;color:#e2e8f0">Rs. {{ number_format($sale->total) }}</td>
            <td style="padding:7px 10px;text-align:right;font-weight:600">
                @if($sale->balanceDue() > 0)
                <span style="color:#fb923c">Rs. {{ number_format($sale->balanceDue()) }}</span>
                @else
                <span style="color:#4ade80">Paid</span>
                @endif
            </td>
            <td style="padding:7px 10px;text-align:center">
                @if($sale->credit_doc_url)
                <a href="{{ $sale->credit_doc_url }}" target="_blank" rel="noopener" style="font-size:10px;padding:2px 8px;border-radius:10px;background:#14532d;color:#4ade80;text-decoration:none"><i class="ti ti-check" style="font-size:11px"></i> View</a>
                @else
                <button type="button" @click="start({{ $sale->id }}, '{{ $sale->invoice_no }}')" style="font-size:10px;padding:3px 9px;border-radius:6px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;cursor:pointer"><i class="ti ti-camera" style="font-size:11px"></i> Attach</button>
                @endif
            </td>
            <td style="padding:7px 10px;text-align:center;white-space:nowrap">
                <a href="{{ route('sales.receipt',$sale->id) }}" target="_blank" rel="noopener" title="View / print the bill" style="font-size:10px;padding:3px 8px;border-radius:6px;background:#1e2130;border:.5px solid #2a2d3a;color:#94a3b8;text-decoration:none"><i class="ti ti-receipt" style="font-size:11px"></i> Bill</a>
                @if($sale->balanceDue() > 0)
                @can('payments.in.create')
                <button type="button" onclick="collectFor({{ $sale->id }}, '{{ $sale->invoice_no }}', {{ $sale->balanceDue() }})" title="Record a repayment" style="font-size:10px;padding:3px 8px;border-radius:6px;background:#14532d;border:.5px solid #166534;color:#4ade80;cursor:pointer;margin-left:4px"><i class="ti ti-cash" style="font-size:11px"></i> Collect</button>
                @endcan
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="padding:18px;text-align:center;color:#4a5568">No credit bills yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
</div>
@include('partials.credit_evidence')

@can('payments.in.create')
{{-- Collect a repayment against a credit bill (reuses the Payments-In flow) --}}
<div id="collectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:80;align-items:center;justify-content:center" onclick="if(event.target===this)closeCollect()">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:12px;padding:20px;width:340px">
        <div style="font-size:14px;font-weight:600;color:#e2e8f0;margin-bottom:4px;display:flex;align-items:center;gap:6px"><i class="ti ti-cash" style="color:#4ade80"></i> Collect payment</div>
        <div id="collectInvoice" style="font-size:11px;color:#64748b;margin-bottom:14px"></div>
        <form method="POST" action="{{ route('payments.in.store') }}">
            @csrf
            <input type="hidden" name="sale_id" id="collectSaleId">
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Amount received (Rs.)</label>
            <input type="number" name="amount" id="collectAmount" min="1" step="0.01" required
                   style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:14px;padding:9px 10px;outline:none;margin-bottom:10px">
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Method</label>
            <select name="method" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:13px;padding:9px 10px;outline:none;margin-bottom:10px">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank">Bank transfer</option>
                <option value="cheque">Cheque</option>
            </select>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Into account</label>
            <select name="account_id" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:13px;padding:9px 10px;outline:none;margin-bottom:16px">
                @foreach($accounts as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                @endforeach
            </select>
            <div style="display:flex;gap:8px">
                <button type="button" onclick="closeCollect()" style="flex:1;height:38px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:7px;color:#94a3b8;font-size:13px;cursor:pointer">Cancel</button>
                <button type="submit" style="flex:1;height:38px;background:#14532d;border:.5px solid #166534;border-radius:7px;color:#4ade80;font-size:13px;font-weight:600;cursor:pointer">Collect</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
function collectFor(saleId, invoice, due) {
    document.getElementById('collectSaleId').value = saleId;
    document.getElementById('collectInvoice').textContent = 'Invoice ' + invoice + ' · balance due Rs. ' + Number(due).toLocaleString();
    const amt = document.getElementById('collectAmount');
    amt.value = due; amt.setAttribute('max', due);
    document.getElementById('collectModal').style.display = 'flex';
    amt.focus(); amt.select();
}
function closeCollect() { document.getElementById('collectModal').style.display = 'none'; }
</script>
@endpush
@endcan
</div>
@endsection
