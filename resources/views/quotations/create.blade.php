{{-- quotations/create.blade.php --}}
@extends('layouts.app')
@section('title','New Quotation')
@section('page-title','New Quotation / Estimate')
@section('content')
@php $ri = 'background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none'; @endphp
<div style="padding:14px 16px;max-width:700px">
<form method="POST" action="{{ route('quotations.store') }}" onsubmit="return qValidate()">
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
            <div style="position:relative">
                <input type="text" class="qi-search" placeholder="Search product..." autocomplete="off" oninput="qSearch(this,0)" style="{{ $ri }};width:100%;box-sizing:border-box">
                <input type="hidden" name="items[0][product_id]">
                <div class="qi-drop" id="qdrop-0" style="display:none;position:absolute;top:28px;left:0;right:0;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;z-index:20;max-height:150px;overflow-y:auto"></div>
            </div>
            <input type="number" name="items[0][quantity]" placeholder="1" value="1" min="0.001" step="0.001" oninput="qCalcRow(this)" style="{{ $ri }}">
            <input type="number" name="items[0][unit_price]" placeholder="0.00" min="0" step="0.01" oninput="qCalcRow(this)" style="{{ $ri }}">
            <input type="number" name="items[0][subtotal]" placeholder="0.00" readonly style="{{ $ri }};color:#a5b4fc">
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
const qProducts = {!! json_encode($products) !!};
let qi = 1;

function qSearch(el, idx) {
    const q = el.value.toLowerCase().trim();
    const drop = document.getElementById('qdrop-' + idx);
    // typing invalidates any previously chosen product until one is picked again
    el.closest('.qi-row').querySelector('[name*="[product_id]"]').value = '';
    if (!q) { drop.style.display = 'none'; return; }
    const matches = qProducts.filter(p => p.name.toLowerCase().includes(q) || (p.barcode && String(p.barcode).includes(q))).slice(0, 8);
    if (!matches.length) { drop.style.display = 'none'; return; }
    drop.innerHTML = matches.map(p =>
        `<div onclick="qSelect(${idx},${p.id},${JSON.stringify(p.name)},${p.price})" style="padding:6px 10px;cursor:pointer;font-size:11px;color:#e2e8f0;border-bottom:.5px solid #2a2d3a;display:flex;justify-content:space-between" onmouseover="this.style.background='#312e81'" onmouseout="this.style.background=''"><span>${p.name}</span><span style="color:#a5b4fc">Rs. ${Number(p.price).toLocaleString()}</span></div>`
    ).join('');
    drop.style.display = 'block';
}
function qSelect(idx, id, name, price) {
    const row = document.querySelectorAll('.qi-row')[idx];
    row.querySelector('.qi-search').value = name;
    row.querySelector('[name*="[product_id]"]').value = id;
    const up = row.querySelector('[name*="[unit_price]"]');
    if (!parseFloat(up.value)) up.value = price;
    document.getElementById('qdrop-' + idx).style.display = 'none';
    qCalcRow(up);
}
document.addEventListener('click', e => {
    document.querySelectorAll('.qi-drop').forEach(d => { if (!d.parentElement.contains(e.target)) d.style.display = 'none'; });
});
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
    const total = Math.max(0, sub - disc);
    document.getElementById('q-grand-total').textContent = 'Rs. ' + total.toLocaleString('en-US',{minimumFractionDigits:2});
}
function addQIRow() {
    const d = document.createElement('div');
    d.className = 'qi-row';
    d.style.cssText = 'display:grid;grid-template-columns:2.5fr 1fr 1fr 1fr 28px;gap:4px;margin-bottom:5px';
    const s = '{{ $ri }}';
    d.innerHTML = `<div style="position:relative"><input type="text" class="qi-search" placeholder="Search product..." autocomplete="off" oninput="qSearch(this,${qi})" style="${s};width:100%;box-sizing:border-box"><input type="hidden" name="items[${qi}][product_id]"><div class="qi-drop" id="qdrop-${qi}" style="display:none;position:absolute;top:28px;left:0;right:0;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;z-index:20;max-height:150px;overflow-y:auto"></div></div><input type="number" name="items[${qi}][quantity]" value="1" min="0.001" step="0.001" oninput="qCalcRow(this)" style="${s}"><input type="number" name="items[${qi}][unit_price]" placeholder="0.00" min="0" step="0.01" oninput="qCalcRow(this)" style="${s}"><input type="number" name="items[${qi}][subtotal]" placeholder="0.00" readonly style="${s};color:#a5b4fc"><div style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#f87171;font-size:13px" onclick="this.closest('.qi-row').remove();qCalcTotal()"><i class="ti ti-x"></i></div>`;
    document.getElementById('quote-items').appendChild(d);
    qi++;
}
function qValidate() {
    const picked = [...document.querySelectorAll('[name*="[product_id]"]')].some(i => i.value);
    if (!picked) { alert('Pick at least one product from the search results.'); return false; }
    return true;
}
</script>
@endpush
@endsection
