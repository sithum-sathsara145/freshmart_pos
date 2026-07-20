{{-- purchases/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Purchase #'.$purchase->bill_no)
@section('page-title','Edit Purchase — '.$purchase->bill_no)
@section('content')
@php
    $inp = 'background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none';
    $cell = 'background:var(--bg);border:.5px solid var(--border);border-radius:5px;color:var(--text);font-size:11px;padding:5px 7px;outline:none;width:100%';
    $cols = '2.1fr 1fr .9fr 1fr 1fr 1fr .9fr 26px';
    $initialItems = $purchase->items->map(fn($i) => [
        '_k' => 'e' . $i->id, 'custom' => ! $i->product_id,
        'id' => $i->product_id, 'name' => $i->product?->name ?? $i->name, 'sku' => $i->product?->sku,
        'barcode' => $i->product?->barcode, 'unit' => $i->product?->unit,
        'is_weighed' => (bool) $i->product?->is_weighed, 'stock' => 0,
        'cur_cost' => $i->unit_price, 'cur_sale' => $i->sale_price, 'cur_mrp' => $i->mrp,
        'batch_no' => $i->batch_no, 'qty' => (float) $i->quantity, 'unit_price' => (float) $i->unit_price,
        'mrp' => $i->mrp, 'sale_price' => $i->sale_price, 'existing' => true,
    ]);
@endphp
<div style="padding:14px 16px;max-width:960px" x-data="purchaseEditForm(@js($initialItems))">
<form method="POST" action="{{ route('purchases.update',$purchase) }}" @submit="onSubmit($event)">
@csrf
@method('PUT')

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Purchase details</div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Supplier</label>
            <div style="{{ $inp }};width:100%;color:var(--text-3);box-sizing:border-box">{{ $purchase->supplier?->name }}</div>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Purchase date *</label>
            <input type="date" name="purchase_date" value="{{ old('purchase_date',\Carbon\Carbon::parse($purchase->purchase_date)->toDateString()) }}" required style="{{ $inp }};width:100%;box-sizing:border-box">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Due date</label>
            <input type="date" name="due_date" value="{{ old('due_date',$purchase->due_date) }}" style="{{ $inp }};width:100%;box-sizing:border-box">
        </div>
    </div>
    <div>
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Notes</label>
        <input type="text" name="notes" value="{{ old('notes',$purchase->notes) }}" style="{{ $inp }};width:100%;box-sizing:border-box">
    </div>
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2)">Items</div>
        <button type="button" @click="addCustom()" style="height:26px;padding:0 10px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;color:var(--text-2);font-size:11px;cursor:pointer"><i class="ti ti-plus" style="font-size:11px"></i> Custom item</button>
    </div>
    <div style="font-size:11px;color:var(--text-3);margin-bottom:10px">
        <i class="ti ti-lock" style="font-size:11px"></i>
        Weighed items already on the shelf lock their qty/cost (the running average cost can't be
        un-blended) — remove the line and re-add it to change those. Batch, MRP and sale price stay editable.
    </div>

    {{-- Product search (by name or barcode) --}}
    <div style="position:relative;margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:7px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:0 10px;height:36px">
            <i class="ti ti-search" style="font-size:14px;color:var(--text-3)"></i>
            <input type="text" x-model="query" @input.debounce.200ms="search()" @focus="search()"
                placeholder="Add item — search by name or barcode…" style="flex:1;background:none;border:none;outline:none;color:var(--text);font-size:12px;height:100%">
        </div>
        <div x-show="results.length" @click.away="results=[]" x-cloak
            style="position:absolute;top:40px;left:0;right:0;background:var(--surface);border:.5px solid var(--border);border-radius:6px;z-index:30;max-height:280px;overflow-y:auto;box-shadow:0 10px 28px var(--shadow)">
            <template x-for="r in results" :key="r.id">
                <div @click="add(r)" style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;cursor:pointer;border-bottom:.5px solid var(--surface-3)"
                     onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">
                    <div>
                        <div style="font-size:12px;color:var(--text)" x-text="r.name"></div>
                        <div style="font-size:10px;color:var(--text-3)">
                            <span x-text="'SKU ' + r.sku"></span> ·
                            <span x-text="r.barcode || 'no barcode'"></span> ·
                            <span x-text="'Stock: ' + r.stock + ' ' + r.unit"></span>
                            <span x-show="r.is_weighed" style="color:var(--primary-text)"> · weighed</span>
                        </div>
                    </div>
                    <div style="font-size:11px;color:var(--success)" x-text="'Rs. ' + r.price"></div>
                </div>
            </template>
        </div>
    </div>

    {{-- Header --}}
    <div style="display:grid;grid-template-columns:{{ $cols }};gap:5px;font-size:10px;color:var(--text-4);margin-bottom:5px;padding:0 2px">
        <span>Product</span><span>Batch&nbsp;no</span><span>Qty</span><span>Purchase&nbsp;Rs.</span><span>MRP&nbsp;Rs.</span><span>Sale&nbsp;Rs.</span><span>Subtotal</span><span></span>
    </div>

    {{-- Rows --}}
    <template x-for="(it,i) in items" :key="it._k">
        <div style="margin-bottom:6px">
            <div style="display:grid;grid-template-columns:{{ $cols }};gap:5px;align-items:center">
                <input type="hidden" :name="`items[${i}][product_id]`" :value="it.custom ? '' : it.id">
                <div style="overflow:hidden">
                    <template x-if="!it.custom">
                        <div>
                            <div style="font-size:11px;color:var(--text);white-space:nowrap;text-overflow:ellipsis;overflow:hidden" x-text="it.name"></div>
                            <div style="font-size:9px;color:var(--text-3);white-space:nowrap;text-overflow:ellipsis;overflow:hidden" x-text="it.barcode || it.sku"></div>
                            <input type="hidden" :name="`items[${i}][name]`" :value="it.name">
                        </div>
                    </template>
                    <template x-if="it.custom">
                        <input type="text" :name="`items[${i}][name]`" x-model="it.name" placeholder="Custom item name" required style="{{ $cell }}">
                    </template>
                </div>
                <input type="text" :name="`items[${i}][batch_no]`" x-model="it.batch_no" placeholder="optional" style="{{ $cell }}">
                <input type="number" :name="`items[${i}][quantity]`" x-model.number="it.qty" min="0.001" step="0.001"
                    :readonly="it.is_weighed && it.existing" :style="(it.is_weighed && it.existing) ? '{{ $cell }};opacity:.5;cursor:not-allowed' : '{{ $cell }}'">
                <input type="number" :name="`items[${i}][unit_price]`" x-model.number="it.unit_price" min="0" step="0.01"
                    :readonly="it.is_weighed && it.existing" :style="(it.is_weighed && it.existing) ? '{{ $cell }};opacity:.5;cursor:not-allowed' : '{{ $cell }}'">
                <input type="number" :name="`items[${i}][mrp]`" x-model.number="it.mrp" min="0" step="0.01" :disabled="it.custom" style="{{ $cell }}" :style="it.custom ? 'opacity:.4' : ''">
                <input type="number" :name="`items[${i}][sale_price]`" x-model.number="it.sale_price" min="0" step="0.01" :disabled="it.custom" style="{{ $cell }}" :style="it.custom ? 'opacity:.4' : ''">
                <span style="font-size:11px;color:var(--primary-text);text-align:right" x-text="lineTotal(it).toFixed(2)"></span>
                <div @click="remove(i)" style="width:24px;height:24px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--danger);font-size:12px"><i class="ti ti-x"></i></div>
            </div>
            <div style="font-size:10px;color:var(--text-3);padding:2px 2px 0">
                <template x-if="it.custom">
                    <span>Custom line — recorded on the bill only, not added to stock.</span>
                </template>
                <template x-if="!it.custom && it.is_weighed && it.existing">
                    <span><i class="ti ti-lock" style="font-size:9px"></i> Qty/cost locked — already on the shelf at this average cost.</span>
                </template>
                <template x-if="!it.custom && it.is_weighed && !it.existing">
                    <span>Weighted-avg cost → <b style="color:var(--primary-text)" x-text="'Rs. ' + wac(it).toFixed(2)"></b> · set the sale price manually.</span>
                </template>
            </div>
        </div>
    </template>
    <div x-show="items.length === 0" style="text-align:center;color:var(--text-4);font-size:11px;padding:14px">Search above to add items.</div>

    {{-- Totals --}}
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:14px;padding-top:12px;border-top:.5px solid var(--border)">
        <div><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Discount (Rs.)</label><input type="number" name="discount_amount" x-model.number="discount" min="0" style="{{ $inp }};width:100%;box-sizing:border-box"></div>
        <div><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Tax (Rs.)</label><input type="number" name="tax_amount" x-model.number="tax" min="0" style="{{ $inp }};width:100%;box-sizing:border-box"></div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Paid so far (Rs.)</label>
            <div style="{{ $inp }};width:100%;color:var(--text-3);box-sizing:border-box">Rs. {{ number_format($purchase->paid_amount,2) }} — add more from the Pay button on the purchase page</div>
        </div>
    </div>
    <div style="margin-top:10px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:10px 12px;display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:12px;color:var(--text-3)">Total amount</span>
        <span style="font-size:16px;font-weight:500;color:var(--primary-text)" x-text="'Rs. ' + total.toLocaleString('en-US',{minimumFractionDigits:2})"></span>
    </div>
</div>

<div style="display:flex;gap:8px">
    <a href="{{ route('purchases.show',$purchase) }}" style="height:36px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Changes</button>
</div>
</form>
</div>

@push('scripts')
<script>
function purchaseEditForm(initialItems) {
    return {
        query: '', results: [], items: initialItems || [],
        discount: {{ (float) $purchase->discount_amount }}, tax: {{ (float) $purchase->tax_amount }},
        uid: 0,

        search() {
            const q = this.query.trim();
            if (!q) { this.results = []; return; }
            fetch(`/api/products/search?q=${encodeURIComponent(q)}&category=`)
                .then(r => r.json())
                .then(list => { this.results = list.filter(p => !this.items.find(x => !x.custom && x.id === p.id)); })
                .catch(() => { this.results = []; });
        },
        add(p) {
            if (!this.items.find(x => !x.custom && x.id === p.id)) {
                this.items.push({
                    _k: 'n' + (++this.uid), custom: false,
                    id: p.id, name: p.name, sku: p.sku, barcode: p.barcode, unit: p.unit,
                    is_weighed: p.is_weighed, stock: p.stock, existing: false,
                    cur_cost: p.purchase_price, cur_sale: p.price, cur_mrp: p.mrp,
                    batch_no: '', qty: 1, unit_price: p.purchase_price, mrp: p.mrp || '', sale_price: p.price,
                });
            }
            this.query = ''; this.results = [];
        },
        addCustom() {
            this.items.push({ _k: 'n' + (++this.uid), custom: true, id: null, name: '', batch_no: '', qty: 1, unit_price: 0, mrp: '', sale_price: '', existing: false });
            this.query = ''; this.results = [];
        },
        remove(i) { this.items.splice(i, 1); },
        lineTotal(it) { return (parseFloat(it.qty) || 0) * (parseFloat(it.unit_price) || 0); },
        get subtotal() { return this.items.reduce((a, it) => a + this.lineTotal(it), 0); },
        get total() { return Math.max(0, this.subtotal - (parseFloat(this.discount) || 0) + (parseFloat(this.tax) || 0)); },

        // Projected weighted-average cost for a newly-added weighed item.
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
