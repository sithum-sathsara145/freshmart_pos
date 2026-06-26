{{-- quotations/create.blade.php --}}
@extends('layouts.app')
@section('title','New Quotation')
@section('page-title','New Quotation / Estimate')
@section('content')
<div style="padding:14px 16px;max-width:700px">
<form method="POST" action="{{ route('quotations.store') }}">
@csrf
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Quotation details</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Customer</label>
            <select name="customer_id" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
                <option value="">Walk-in customer</option>
                @foreach($customers as $c)<option value="{{ $c->id }}" {{ old('customer_id')==$c->id?'selected':'' }}>{{ $c->name }} ({{ $c->phone }})</option>@endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Valid until</label>
            <input type="date" name="valid_till" value="{{ old('valid_till', now()->addDays(30)->toDateString()) }}"
                style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
        </div>
    </div>
    <div style="margin-bottom:12px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Notes (visible to customer)</label>
        <input type="text" name="notes" value="{{ old('notes') }}" placeholder="e.g. Prices valid for 30 days"
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>

    <div style="font-size:11px;color:#64748b;margin-bottom:6px">Items</div>
    <div style="display:grid;grid-template-columns:2.5fr 1fr 1fr 1fr 28px;gap:4px;font-size:10px;color:#4a5568;margin-bottom:4px;padding:0 2px">
        <span>Product</span><span>Qty</span><span>Unit price</span><span>Subtotal</span><span></span>
    </div>
    <div id="quote-items">
        <div class="qi-row" style="display:grid;grid-template-columns:2.5fr 1fr 1fr 1fr 28px;gap:4px;margin-bottom:5px">
            <input type="text" name="items[0][product_id]" placeholder="Product name" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][quantity]" placeholder="1" min="0.001" step="0.001" oninput="qCalcRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][unit_price]" placeholder="0.00" min="0" step="0.01" oninput="qCalcRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][subtotal]" placeholder="0.00" readonly style="background:#0f1117;border:.5px solid #1a1d2a;border-radius:5px;color:#a5b4fc;font-size:11px;padding:5px 8px;outline:none">
            <div style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#f87171;font-size:13px" onclick="this.closest('.qi-row').remove();qCalcTotal()"><i class="ti ti-x"></i></div>
        </div>
    </div>
    <button type="button" onclick="addQIRow()" style="height:28px;padding:0 10px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;color:#94a3b8;font-size:11px;cursor:pointer;margin-top:4px"><i class="ti ti-plus" style="font-size:11px"></i> Add item</button>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Discount (Rs.)</label>
            <input type="number" name="discount_amount" value="{{ old('discount_amount',0) }}" min="0" step="0.01" oninput="qCalcTotal()"
                style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
        </div>
        <div style="display:flex;flex-direction:column;justify-content:flex-end">
            <div style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:10px 12px;display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:12px;color:#64748b">Total</span>
                <span style="font-size:16px;font-weight:500;color:#a5b4fc" id="q-grand-total">Rs. 0</span>
            </div>
        </div>
    </div>
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('quotations.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">
        <i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Quotation
    </button>
</div>
</form>
</div>

@push('scripts')
<script>
let qi = 1;
function qCalcRow(el) {
    const row = el.closest('.qi-row');
    const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value)||0;
    const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value)||0;
    row.querySelector('[name*="[subtotal]"]').value = (qty*price).toFixed(2);
    qCalcTotal();
}
function qCalcTotal() {
    let sub = 0;
    document.querySelectorAll('[name*="[subtotal]"]').forEach(i=>sub+=parseFloat(i.value)||0);
    const disc = parseFloat(document.querySelector('[name="discount_amount"]').value)||0;
    const total = sub - disc;
    document.getElementById('q-grand-total').textContent = 'Rs. ' + total.toLocaleString('en-US',{minimumFractionDigits:2});
}
function addQIRow() {
    const d = document.createElement('div');
    d.className = 'qi-row';
    d.style.cssText = 'display:grid;grid-template-columns:2.5fr 1fr 1fr 1fr 28px;gap:4px;margin-bottom:5px';
    d.innerHTML = `<input type="text" name="items[${qi}][product_id]" placeholder="Product name" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none"><input type="number" name="items[${qi}][quantity]" placeholder="1" min="0.001" step="0.001" oninput="qCalcRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none"><input type="number" name="items[${qi}][unit_price]" placeholder="0.00" min="0" step="0.01" oninput="qCalcRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none"><input type="number" name="items[${qi}][subtotal]" placeholder="0.00" readonly style="background:#0f1117;border:.5px solid #1a1d2a;border-radius:5px;color:#a5b4fc;font-size:11px;padding:5px 8px;outline:none"><div style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#f87171;font-size:13px" onclick="this.closest('.qi-row').remove();qCalcTotal()"><i class="ti ti-x"></i></div>`;
    document.getElementById('quote-items').appendChild(d);
    qi++;
}
</script>
@endpush
@endsection
