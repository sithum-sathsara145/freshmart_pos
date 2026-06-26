{{-- purchases/create.blade.php --}}
@extends('layouts.app')
@section('title','New Purchase')
@section('page-title','New Purchase Order')
@section('content')
<div style="padding:14px 16px;max-width:700px">
<form method="POST" action="{{ route('purchases.store') }}">
@csrf
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Purchase details</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Supplier *</label>
            <select name="supplier_id" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
                <option value="">— Select supplier —</option>
                @foreach($suppliers as $s)<option value="{{ $s->id }}" {{ old('supplier_id',request('supplier_id'))==$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Purchase date *</label>
            <input type="date" name="purchase_date" value="{{ old('purchase_date',today()->toDateString()) }}" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Due date</label>
            <input type="date" name="due_date" value="{{ old('due_date') }}" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Payment method</label>
            <select name="payment_method" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
                <option value="cash">Cash</option><option value="bank">Bank transfer</option><option value="cheque">Cheque</option><option value="credit">Credit</option>
            </select>
        </div>
    </div>

    <div style="font-size:11px;color:#64748b;margin-bottom:6px">Items</div>
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 28px;gap:4px;font-size:10px;color:#4a5568;margin-bottom:4px;padding:0 2px">
        <span>Product</span><span>Qty</span><span>Unit price</span><span>Subtotal</span><span></span>
    </div>
    <div id="purchase-items">
        <div class="pi-row" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 28px;gap:4px;margin-bottom:5px">
            <input type="text" name="items[0][product_id]" placeholder="Product name" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][quantity]" placeholder="0" min="0.001" step="0.001" oninput="calcRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][unit_price]" placeholder="0.00" min="0" step="0.01" oninput="calcRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][subtotal]" placeholder="0.00" readonly style="background:#0f1117;border:.5px solid #1a1d2a;border-radius:5px;color:#a5b4fc;font-size:11px;padding:5px 8px;outline:none">
            <div style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#f87171;font-size:13px" onclick="this.closest('.pi-row').remove();calcTotal()"><i class="ti ti-x"></i></div>
        </div>
    </div>
    <button type="button" onclick="addPIRow()" style="height:28px;padding:0 10px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;color:#94a3b8;font-size:11px;cursor:pointer;margin-top:4px"><i class="ti ti-plus" style="font-size:11px"></i> Add item</button>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:14px;padding-top:12px;border-top:.5px solid #2a2d3a">
        <div><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Discount (Rs.)</label><input type="number" name="discount_amount" value="0" min="0" oninput="calcTotal()" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none"></div>
        <div><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Tax (Rs.)</label><input type="number" name="tax_amount" value="0" min="0" oninput="calcTotal()" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none"></div>
        <div><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Paid now (Rs.)</label><input type="number" name="paid_amount" id="paid-amount" value="0" min="0" step="0.01" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none"></div>
    </div>
    <div style="margin-top:10px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:10px 12px;display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:12px;color:#64748b">Total amount</span>
        <span style="font-size:16px;font-weight:500;color:#a5b4fc" id="grand-total">Rs. 0</span>
    </div>
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('purchases.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Purchase</button>
</div>
</form>
</div>
@push('scripts')
<script>
let pi = 1;
function calcRow(el) {
    const row = el.closest('.pi-row');
    const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value)||0;
    const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value)||0;
    row.querySelector('[name*="[subtotal]"]').value = (qty*price).toFixed(2);
    calcTotal();
}
function calcTotal() {
    let sub = 0;
    document.querySelectorAll('[name*="[subtotal]"]').forEach(i=>sub+=parseFloat(i.value)||0);
    const disc = parseFloat(document.querySelector('[name="discount_amount"]').value)||0;
    const tax  = parseFloat(document.querySelector('[name="tax_amount"]').value)||0;
    const total = sub - disc + tax;
    document.getElementById('grand-total').textContent = 'Rs. ' + total.toLocaleString('en-US',{minimumFractionDigits:2});
}
function addPIRow() {
    const d = document.createElement('div');
    d.className = 'pi-row';
    d.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr 1fr 28px;gap:4px;margin-bottom:5px';
    d.innerHTML = `<input type="text" name="items[${pi}][product_id]" placeholder="Product name" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none"><input type="number" name="items[${pi}][quantity]" placeholder="0" min="0.001" step="0.001" oninput="calcRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none"><input type="number" name="items[${pi}][unit_price]" placeholder="0.00" min="0" step="0.01" oninput="calcRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none"><input type="number" name="items[${pi}][subtotal]" placeholder="0.00" readonly style="background:#0f1117;border:.5px solid #1a1d2a;border-radius:5px;color:#a5b4fc;font-size:11px;padding:5px 8px;outline:none"><div style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#f87171;font-size:13px" onclick="this.closest('.pi-row').remove();calcTotal()"><i class="ti ti-x"></i></div>`;
    document.getElementById('purchase-items').appendChild(d);
    pi++;
}
</script>
@endpush
@endsection
