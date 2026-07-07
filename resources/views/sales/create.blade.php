{{-- sales/create.blade.php --}}
@extends('layouts.app')
@section('title','New Sale')
@section('page-title','New Sale')

@section('content')
<div style="padding:14px 16px">

@if(!empty($prefill))
<div style="display:flex;align-items:center;gap:8px;background:#1e2130;border:.5px solid #534AB7;border-radius:8px;padding:9px 12px;margin-bottom:12px;font-size:12px;color:#a5b4fc">
    <i class="ti ti-file-invoice" style="font-size:14px"></i>
    Converting quotation <strong style="color:#e2e8f0;margin:0 3px">{{ $prefill['quote_no'] }}</strong> — review the items and save to complete the sale.
</div>
@endif

<form id="sale-form" method="POST" action="{{ route('sales.store') }}">
@csrf
@if(!empty($prefill))<input type="hidden" name="from_quote" value="{{ $prefill['quote_id'] }}">@endif

<div style="display:grid;grid-template-columns:1fr 310px;gap:14px;align-items:start">

{{-- Left — Items --}}
<div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Sale items</div>

    {{-- Column headers --}}
    <div style="display:grid;grid-template-columns:2.5fr 1fr 1fr 1fr 1fr 28px;gap:5px;font-size:10px;color:#4a5568;margin-bottom:5px;padding:0 2px">
        <span>Product</span><span>Qty</span><span>Unit price</span><span>Disc. %</span><span>Subtotal</span><span></span>
    </div>

    <div id="sale-items">
        <div class="sale-row" style="display:grid;grid-template-columns:2.5fr 1fr 1fr 1fr 1fr 28px;gap:5px;margin-bottom:6px">
            <div style="position:relative">
                <input type="text" name="items[0][product_search]" placeholder="Search product..." autocomplete="off"
                    oninput="searchProduct(this,0)"
                    style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
                <input type="hidden" name="items[0][product_id]">
                <div class="product-dropdown" id="drop-0" style="display:none;position:absolute;top:28px;left:0;right:0;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;z-index:20;max-height:140px;overflow-y:auto"></div>
            </div>
            <input type="number" name="items[0][quantity]" value="1" min="0.001" step="0.001" oninput="calcSaleRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][unit_price]" placeholder="0.00" min="0" step="0.01" oninput="calcSaleRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][discount_pct]" value="0" min="0" max="100" step="0.01" oninput="calcSaleRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][subtotal]" placeholder="0.00" readonly style="background:#0f1117;border:.5px solid #1a1d2a;border-radius:5px;color:#a5b4fc;font-size:11px;padding:5px 8px;outline:none">
            <div style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#f87171;font-size:13px" onclick="removeSaleRow(this)"><i class="ti ti-x"></i></div>
        </div>
    </div>

    <button type="button" onclick="addSaleRow()" style="height:28px;padding:0 10px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;color:#94a3b8;font-size:11px;cursor:pointer;margin-top:4px">
        <i class="ti ti-plus" style="font-size:11px"></i> Add item
    </button>
</div>

{{-- Remark --}}
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px">
    <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Remarks (optional)</label>
    <input type="text" id="sale-note" name="note" placeholder="Internal note..."
        style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
</div>
</div>

{{-- Right — Summary + submit --}}
<div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:10px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Sale summary</div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Customer</label>
        <select name="customer_id" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="">Walk-in customer</option>
            @foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>@endforeach
        </select>
    </div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Coupon code</label>
        <div style="display:flex;gap:6px">
            <input type="text" name="coupon_code" id="coupon-code" placeholder="Enter code" style="flex:1;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <button type="button" onclick="applyCoupon()" style="height:34px;padding:0 10px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:11px;cursor:pointer">Apply</button>
        </div>
        <div id="coupon-msg" style="font-size:10px;margin-top:3px"></div>
    </div>

    <div style="border-top:.5px solid #2a2d3a;padding-top:10px;margin-top:6px">
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-bottom:4px">
            <span>Subtotal</span><span id="sum-subtotal">Rs. 0.00</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#4ade80;margin-bottom:4px" id="discount-row" style="display:none">
            <span>Discount</span><span id="sum-discount">Rs. 0.00</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-bottom:4px" id="tax-row">
            <span>Tax</span><span id="sum-tax">Rs. 0.00</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:15px;font-weight:500;color:#e2e8f0;padding-top:6px;border-top:.5px solid #2a2d3a">
            <span>Total</span><span id="sum-total">Rs. 0.00</span>
        </div>
    </div>
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:10px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Payment</div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:6px">Method</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px" id="pay-methods">
            @foreach([['cash','Cash','ti-cash'],['card','Card','ti-credit-card'],['bank_transfer','Bank','ti-building-bank'],['credit','Credit','ti-calendar-due']] as [$val,$lbl,$ico])
            <label style="display:flex;align-items:center;gap:6px;padding:7px 9px;background:#0f1117;border:.5px solid {{ $val==='cash'?'#534AB7':'#2a2d3a' }};border-radius:6px;cursor:pointer;font-size:11px;color:#e2e8f0" id="pm-{{ $val }}">
                <input type="radio" name="payment_method" value="{{ $val }}" {{ $val==='cash'?'checked':'' }} style="accent-color:#818cf8" onchange="selectPM('{{ $val }}')">
                <i class="ti {{ $ico }}" style="font-size:13px"></i>{{ $lbl }}
            </label>
            @endforeach
        </div>
    </div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Account</label>
        <select name="account_id" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }} (Rs. {{ number_format($a->balance) }})</option>@endforeach
        </select>
    </div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Amount paid (Rs.)</label>
        <input type="number" name="paid_amount" id="paid-amount" min="0" step="0.01"
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:14px;font-weight:500;padding:8px 10px;outline:none"
            oninput="calcChange()">
    </div>

    <div style="display:flex;justify-content:space-between;font-size:13px;padding:8px 0;border-top:.5px solid #2a2d3a">
        <span style="color:#64748b">Change</span>
        <span style="color:#4ade80;font-weight:500" id="change-amount">Rs. 0.00</span>
    </div>
</div>

{{-- Hidden fields populated by JS --}}
<input type="hidden" name="sale_items_json" id="sale-items-json">
<input type="hidden" name="subtotal" id="h-subtotal">
<input type="hidden" name="discount_amount" id="h-discount">
<input type="hidden" name="tax_amount" id="h-tax">
<input type="hidden" name="total" id="h-total">
<input type="hidden" name="change_amount" id="h-change">
<input type="hidden" name="note" id="h-note">

<button type="submit" onclick="submitSale(event)" style="width:100%;height:42px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:7px;font-size:13px;font-weight:500;cursor:pointer">
    <i class="ti ti-check" style="font-size:15px;margin-right:5px"></i>Save Sale
</button>

<a href="{{ route('pos') }}" style="display:flex;align-items:center;justify-content:center;gap:5px;margin-top:7px;height:34px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;text-decoration:none">
    <i class="ti ti-scan" style="font-size:13px"></i>Use Full POS instead
</a>
</div>

</div>
</form>
</div>

@push('scripts')
<script>
// ── Product search ──────────────────────────────────────
const products = {!! json_encode($products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'price'=>$p->sale_price,'barcode'=>$p->barcode,'unit'=>$p->unit,'stock'=>$p->current_stock])) !!};

let rowIdx = 1;

function searchProduct(el, idx) {
    const q = el.value.toLowerCase().trim();
    const drop = document.getElementById('drop-' + idx);
    if (!q) { drop.style.display = 'none'; return; }
    const matches = products.filter(p => p.name.toLowerCase().includes(q) || (p.barcode && p.barcode.includes(q))).slice(0, 8);
    if (!matches.length) { drop.style.display = 'none'; return; }
    drop.innerHTML = matches.map(p =>
        `<div onclick="selectProduct(${idx},${p.id},'${p.name.replace(/'/g,"\\'")}',${p.price})" style="padding:6px 10px;cursor:pointer;font-size:11px;color:#e2e8f0;border-bottom:.5px solid #2a2d3a;display:flex;justify-content:space-between"
            onmouseover="this.style.background='#312e81'" onmouseout="this.style.background=''">
            <span>${p.name}</span><span style="color:#a5b4fc">Rs. ${Number(p.price).toLocaleString()}</span>
        </div>`
    ).join('');
    drop.style.display = 'block';
}

function selectProduct(idx, id, name, price) {
    const row = document.querySelectorAll('.sale-row')[idx];
    row.querySelector('[name*="[product_search]"]').value = name;
    row.querySelector('[name*="[product_id]"]').value = id;
    row.querySelector('[name*="[unit_price]"]').value = price;
    document.getElementById('drop-' + idx).style.display = 'none';
    calcSaleRow(row.querySelector('[name*="[unit_price]"]'));
}

document.addEventListener('click', e => {
    document.querySelectorAll('.product-dropdown').forEach(d => {
        if (!d.parentElement.contains(e.target)) d.style.display = 'none';
    });
});

// ── Row calculations ──────────────────────────────────
function calcSaleRow(el) {
    const row = el.closest('.sale-row');
    const qty   = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
    const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value) || 0;
    const disc  = parseFloat(row.querySelector('[name*="[discount_pct]"]').value) || 0;
    const sub   = qty * price * (1 - disc / 100);
    row.querySelector('[name*="[subtotal]"]').value = sub.toFixed(2);
    calcTotals();
}

function calcTotals() {
    let sub = 0;
    document.querySelectorAll('[name*="[subtotal]"]').forEach(i => sub += parseFloat(i.value) || 0);
    const disc = 0; // coupon handled server-side
    const tax  = 0;
    const total = sub - disc + tax;
    document.getElementById('sum-subtotal').textContent = 'Rs. ' + sub.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('sum-total').textContent    = 'Rs. ' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('h-subtotal').value = sub.toFixed(2);
    document.getElementById('h-discount').value = disc.toFixed(2);
    document.getElementById('h-tax').value      = tax.toFixed(2);
    document.getElementById('h-total').value    = total.toFixed(2);
    // Auto-fill paid amount if cash
    const pm = document.querySelector('[name="payment_method"]:checked')?.value;
    if (pm === 'cash') { document.getElementById('paid-amount').value = total.toFixed(2); }
    calcChange();
}

function calcChange() {
    const total = parseFloat(document.getElementById('h-total').value) || 0;
    const paid  = parseFloat(document.getElementById('paid-amount').value) || 0;
    const change = Math.max(0, paid - total);
    document.getElementById('change-amount').textContent = 'Rs. ' + change.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('h-change').value = change.toFixed(2);
}

// ── Add / Remove rows ─────────────────────────────────
function addSaleRow() {
    const container = document.getElementById('sale-items');
    const div = document.createElement('div');
    div.className = 'sale-row';
    div.style.cssText = 'display:grid;grid-template-columns:2.5fr 1fr 1fr 1fr 1fr 28px;gap:5px;margin-bottom:6px';
    div.innerHTML = `
        <div style="position:relative">
            <input type="text" name="items[${rowIdx}][product_search]" placeholder="Search product..." autocomplete="off"
                oninput="searchProduct(this,${rowIdx})"
                style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="hidden" name="items[${rowIdx}][product_id]">
            <div class="product-dropdown" id="drop-${rowIdx}" style="display:none;position:absolute;top:28px;left:0;right:0;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;z-index:20;max-height:140px;overflow-y:auto"></div>
        </div>
        <input type="number" name="items[${rowIdx}][quantity]" value="1" min="0.001" step="0.001" oninput="calcSaleRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
        <input type="number" name="items[${rowIdx}][unit_price]" placeholder="0.00" min="0" step="0.01" oninput="calcSaleRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
        <input type="number" name="items[${rowIdx}][discount_pct]" value="0" min="0" max="100" step="0.01" oninput="calcSaleRow(this)" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
        <input type="number" name="items[${rowIdx}][subtotal]" placeholder="0.00" readonly style="background:#0f1117;border:.5px solid #1a1d2a;border-radius:5px;color:#a5b4fc;font-size:11px;padding:5px 8px;outline:none">
        <div style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#f87171;font-size:13px" onclick="removeSaleRow(this)"><i class="ti ti-x"></i></div>`;
    container.appendChild(div);
    rowIdx++;
}

function removeSaleRow(el) {
    el.closest('.sale-row').remove();
    calcTotals();
}

// ── Payment method selector ───────────────────────────
function selectPM(val) {
    document.querySelectorAll('[id^="pm-"]').forEach(l => {
        l.style.border = '.5px solid #2a2d3a';
    });
    document.getElementById('pm-' + val).style.border = '.5px solid #534AB7';
    calcTotals();
}

// ── Coupon apply ──────────────────────────────────────
function applyCoupon() {
    const code = document.getElementById('coupon-code').value.trim();
    const msg  = document.getElementById('coupon-msg');
    if (!code) { msg.textContent = ''; return; }
    // Server validation on submit; just show pending
    msg.style.color = '#fb923c';
    msg.textContent = 'Coupon "' + code + '" will be applied on save.';
}

// ── Submit — copy note to hidden ─────────────────────
function submitSale(e) {
    document.getElementById('h-note').value = document.getElementById('sale-note').value;
    // Validate at least 1 product
    const hasProduct = [...document.querySelectorAll('[name*="[product_id]"]')].some(i => i.value);
    if (!hasProduct) {
        e.preventDefault();
        alert('Please add at least one product.');
    }
}

// ── Prefill from quotation (Convert to sale) ──────────
@if(!empty($prefill))
(function () {
    const PF = @json($prefill);
    function apply() {
        const cs = document.querySelector('[name="customer_id"]');
        if (cs && PF.customer_id) cs.value = PF.customer_id;
        (PF.items || []).forEach((it, i) => {
            if (i > 0) addSaleRow();
            const row = document.querySelectorAll('.sale-row')[i];
            if (!row) return;
            row.querySelector('[name*="[product_search]"]').value = it.name || '';
            row.querySelector('[name*="[product_id]"]').value    = it.product_id || '';
            row.querySelector('[name*="[quantity]"]').value      = it.quantity;
            row.querySelector('[name*="[unit_price]"]').value    = it.unit_price;
            calcSaleRow(row.querySelector('[name*="[unit_price]"]'));
        });
        calcTotals();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', apply);
    else apply();
})();
@endif
</script>
@endpush
@endsection
