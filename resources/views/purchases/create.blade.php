{{-- purchases/create.blade.php --}}
@extends('layouts.app')
@section('title','New Purchase')
@section('page-title','New Purchase Order')
@section('content')
@php
    $inp = 'background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none';
    $cell = 'background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 7px;outline:none;width:100%';
    $cols = '2.1fr 1fr .9fr 1fr 1fr 1fr .9fr 26px';
@endphp
<div style="padding:14px 16px;max-width:960px" x-data="purchaseForm()">
<form method="POST" action="{{ route('purchases.store') }}" @submit="onSubmit($event)">
@csrf

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Purchase details</div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Supplier *</label>
            <select name="supplier_id" required style="{{ $inp }};width:100%">
                <option value="">— Select supplier —</option>
                @foreach($suppliers as $s)<option value="{{ $s->id }}" {{ old('supplier_id',request('supplier_id'))==$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Purchase date *</label>
            <input type="date" name="purchase_date" value="{{ old('purchase_date',today()->toDateString()) }}" required style="{{ $inp }};width:100%">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Payment method</label>
            <select name="payment_method" style="{{ $inp }};width:100%">
                <option value="cash">Cash</option><option value="bank">Bank transfer</option><option value="cheque">Cheque</option><option value="credit">Credit</option>
            </select>
        </div>
    </div>
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:8px">Items</div>

    {{-- Product search (by name or barcode) --}}
    <div style="position:relative;margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:7px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:0 10px;height:36px">
            <i class="ti ti-search" style="font-size:14px;color:#64748b"></i>
            <input type="text" x-model="query" @input.debounce.200ms="search()" @focus="search()"
                placeholder="Add item — search by name or barcode…" style="flex:1;background:none;border:none;outline:none;color:#e2e8f0;font-size:12px;height:100%">
        </div>
        <div x-show="results.length" @click.away="results=[]" x-cloak
            style="position:absolute;top:40px;left:0;right:0;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;z-index:30;max-height:280px;overflow-y:auto;box-shadow:0 10px 28px rgba(0,0,0,.5)">
            <template x-for="r in results" :key="r.id">
                <div @click="add(r)" style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;cursor:pointer;border-bottom:.5px solid #1a1d2a"
                     onmouseover="this.style.background='#1e2130'" onmouseout="this.style.background=''">
                    <div>
                        <div style="font-size:12px;color:#e2e8f0" x-text="r.name"></div>
                        <div style="font-size:10px;color:#64748b">
                            <span x-text="'SKU ' + r.sku"></span> ·
                            <span x-text="r.barcode || 'no barcode'"></span> ·
                            <span x-text="'Stock: ' + r.stock + ' ' + r.unit"></span>
                            <span x-show="r.is_weighed" style="color:#a5b4fc"> · weighed</span>
                        </div>
                    </div>
                    <div style="font-size:11px;color:#4ade80" x-text="'Rs. ' + r.price"></div>
                </div>
            </template>
        </div>
    </div>

    {{-- Header --}}
    <div style="display:grid;grid-template-columns:{{ $cols }};gap:5px;font-size:10px;color:#4a5568;margin-bottom:5px;padding:0 2px">
        <span>Product</span><span>Batch&nbsp;no</span><span>Qty</span><span>Purchase&nbsp;Rs.</span><span>MRP&nbsp;Rs.</span><span>Sale&nbsp;Rs.</span><span>Subtotal</span><span></span>
    </div>

    {{-- Rows --}}
    <template x-for="(it,i) in items" :key="it.id">
        <div style="margin-bottom:6px">
            <div style="display:grid;grid-template-columns:{{ $cols }};gap:5px;align-items:center">
                <input type="hidden" :name="`items[${i}][product_id]`" :value="it.id">
                <div style="overflow:hidden">
                    <div style="font-size:11px;color:#e2e8f0;white-space:nowrap;text-overflow:ellipsis;overflow:hidden" x-text="it.name"></div>
                    <div style="font-size:9px;color:#64748b;white-space:nowrap;text-overflow:ellipsis;overflow:hidden" x-text="it.barcode || it.sku"></div>
                </div>
                <input type="text" :name="`items[${i}][batch_no]`" x-model="it.batch_no" placeholder="optional" style="{{ $cell }}">
                <input type="number" :name="`items[${i}][quantity]`" x-model.number="it.qty" min="0.001" step="0.001" style="{{ $cell }}">
                <input type="number" :name="`items[${i}][unit_price]`" x-model.number="it.unit_price" min="0" step="0.01" style="{{ $cell }}">
                <input type="number" :name="`items[${i}][mrp]`" x-model.number="it.mrp" min="0" step="0.01" style="{{ $cell }}">
                <input type="number" :name="`items[${i}][sale_price]`" x-model.number="it.sale_price" min="0" step="0.01" style="{{ $cell }}">
                <span style="font-size:11px;color:#a5b4fc;text-align:right" x-text="lineTotal(it).toFixed(2)"></span>
                <div @click="remove(i)" style="width:24px;height:24px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#f87171;font-size:12px"><i class="ti ti-x"></i></div>
            </div>
            {{-- Per-row costing hint --}}
            <div style="font-size:10px;color:#64748b;padding:2px 2px 0">
                <template x-if="it.is_weighed">
                    <span>Weighted-avg cost → <b style="color:#a5b4fc" x-text="'Rs. ' + wac(it).toFixed(2)"></b> · set the sale price manually.</span>
                </template>
                <template x-if="!it.is_weighed">
                    <span x-show="Number(it.sale_price) !== Number(it.cur_sale)" style="color:#fbbf24">New sale price — will be offered as a separate price at the POS (same barcode/SKU).</span>
                </template>
            </div>
        </div>
    </template>
    <div x-show="items.length === 0" style="text-align:center;color:#4a5568;font-size:11px;padding:14px">Search above to add items.</div>

    {{-- Totals --}}
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:14px;padding-top:12px;border-top:.5px solid #2a2d3a">
        <div><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Discount (Rs.)</label><input type="number" name="discount_amount" x-model.number="discount" min="0" style="{{ $inp }};width:100%"></div>
        <div><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Tax (Rs.)</label><input type="number" name="tax_amount" x-model.number="tax" min="0" style="{{ $inp }};width:100%"></div>
        <div><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Paid now (Rs.)</label><input type="number" name="paid_amount" x-model.number="paid" min="0" step="0.01" style="{{ $inp }};width:100%"></div>
    </div>
    <div style="margin-top:10px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:10px 12px;display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:12px;color:#64748b">Total amount</span>
        <span style="font-size:16px;font-weight:500;color:#a5b4fc" x-text="'Rs. ' + total.toLocaleString('en-US',{minimumFractionDigits:2})"></span>
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
function purchaseForm() {
    return {
        query: '', results: [], items: [],
        discount: 0, tax: 0, paid: 0,

        search() {
            const q = this.query.trim();
            if (!q) { this.results = []; return; }
            fetch(`/api/products/search?q=${encodeURIComponent(q)}&category=`)
                .then(r => r.json())
                .then(list => { this.results = list; })
                .catch(() => { this.results = []; });
        },
        add(p) {
            if (!this.items.find(x => x.id === p.id)) {
                this.items.push({
                    id: p.id, name: p.name, sku: p.sku, barcode: p.barcode, unit: p.unit,
                    is_weighed: p.is_weighed, stock: p.stock,
                    cur_cost: p.purchase_price, cur_sale: p.price, cur_mrp: p.mrp,
                    batch_no: '', qty: 1, unit_price: p.purchase_price, mrp: p.mrp || '', sale_price: p.price,
                });
            }
            this.query = ''; this.results = [];
        },
        remove(i) { this.items.splice(i, 1); },
        lineTotal(it) { return (parseFloat(it.qty) || 0) * (parseFloat(it.unit_price) || 0); },
        get subtotal() { return this.items.reduce((a, it) => a + this.lineTotal(it), 0); },
        get total() { return Math.max(0, this.subtotal - (parseFloat(this.discount) || 0) + (parseFloat(this.tax) || 0)); },

        // Projected weighted-average cost for a weighed item.
        wac(it) {
            const onHand = parseFloat(it.stock) || 0, curCost = parseFloat(it.cur_cost) || 0;
            const q = parseFloat(it.qty) || 0, c = parseFloat(it.unit_price) || 0;
            const nq = onHand + q;
            return nq > 0 ? ((onHand * curCost) + (q * c)) / nq : c;
        },

        onSubmit(e) {
            if (this.items.length === 0) { e.preventDefault(); alert('Add at least one item.'); }
        },
    };
}
</script>
@endpush
@endsection
