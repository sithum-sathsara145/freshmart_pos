{{-- sale-returns/create.blade.php --}}
@extends('layouts.app')
@section('title','New Sales Return')
@section('page-title','New Sales Return / Cr. Note')
@section('content')
@php
    $inp = 'background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none';
@endphp
<div style="padding:14px 16px;max-width:720px" x-data="returnForm(@js($sales))">
<form method="POST" action="{{ route('sale-returns.store') }}" @submit="onSubmit($event)">
@csrf
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Return details</div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Original invoice *</label>
        <select name="sale_id" required x-model="saleId" @change="pickSale()" style="{{ $inp }};width:100%;box-sizing:border-box">
            <option value="">— Select invoice —</option>
            <template x-for="s in sales" :key="s.id">
                <option :value="s.id" x-text="'#' + s.invoice_no + ' — ' + s.customer + ' — Rs. ' + s.total.toLocaleString()"></option>
            </template>
        </select>
        <div x-show="sales.length === 0" style="font-size:11px;color:var(--text-3);margin-top:5px">No invoices with returnable items.</div>
    </div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Return reason *</label>
        <select name="reason" required style="{{ $inp }};width:100%;box-sizing:border-box">
            <option value="">— Select reason —</option>
            @foreach(['Damaged item','Wrong item delivered','Expired product','Customer changed mind','Quality issue','Other'] as $r)
            <option value="{{ $r }}" {{ old('reason')===$r?'selected':'' }}>{{ $r }}</option>
            @endforeach
        </select>
    </div>

    {{-- Line items of the selected invoice --}}
    <div x-show="lines.length" x-cloak style="margin-top:6px">
        <div style="font-size:12px;color:var(--text-3);margin-bottom:6px">Items to return</div>
        <div style="display:grid;grid-template-columns:2.2fr .8fr .9fr 1fr 1fr;gap:6px;font-size:10px;color:var(--text-4);padding:0 2px 4px">
            <span>Product</span><span style="text-align:center">Sold</span><span style="text-align:center">Left</span><span>Return qty</span><span style="text-align:right">Line refund</span>
        </div>
        <template x-for="(l,i) in lines" :key="l.sale_item_id">
            <div style="display:grid;grid-template-columns:2.2fr .8fr .9fr 1fr 1fr;gap:6px;align-items:center;margin-bottom:6px">
                <input type="hidden" :name="`items[${i}][sale_item_id]`" :value="l.sale_item_id">
                <div style="font-size:11px;color:var(--text);white-space:nowrap;text-overflow:ellipsis;overflow:hidden" x-text="l.name"></div>
                <div style="font-size:11px;color:var(--text-3);text-align:center" x-text="l.sold"></div>
                <div style="font-size:11px;color:var(--text-2);text-align:center" x-text="l.remaining"></div>
                <input type="number" :name="`items[${i}][quantity]`" x-model.number="l.qty"
                    min="0" :max="l.remaining" step="0.001" @input="clampLine(l)"
                    style="{{ $inp }};padding:5px 7px;font-size:11px;width:100%;box-sizing:border-box">
                <div style="font-size:11px;color:var(--danger);text-align:right" x-text="'Rs. ' + ((l.qty||0)*l.unit_price).toFixed(2)"></div>
            </div>
        </template>
    </div>
    <div x-show="saleId && !lines.length" x-cloak style="font-size:11px;color:var(--text-3);padding:6px 2px">This invoice has no returnable items left.</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px;padding-top:12px;border-top:.5px solid var(--border)">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Total refund (Rs.)</label>
            <div style="{{ $inp }};width:100%;box-sizing:border-box;color:var(--danger);font-weight:500" x-text="'Rs. ' + total.toFixed(2)"></div>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Refund method *</label>
            <select name="refund_method" required style="{{ $inp }};width:100%;box-sizing:border-box">
                <option value="cash">Cash refund</option>
                <option value="credit_note">Credit note</option>
                <option value="exchange">Exchange</option>
            </select>
        </div>
    </div>
</div>

<div style="display:flex;gap:8px">
    <a href="{{ route('sale-returns.index') }}" style="height:36px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-arrow-back-up" style="font-size:13px;margin-right:4px"></i>Process Return</button>
</div>
</form>
</div>

@push('scripts')
<script>
function returnForm(sales) {
    return {
        sales: sales || [],
        saleId: '',
        lines: [],

        pickSale() {
            const s = this.sales.find(x => String(x.id) === String(this.saleId));
            this.lines = s ? s.lines.map(l => ({ ...l, qty: 0 })) : [];
        },
        clampLine(l) {
            let q = parseFloat(l.qty) || 0;
            if (q < 0) q = 0;
            if (q > l.remaining) q = l.remaining;
            l.qty = q;
        },
        get total() {
            return this.lines.reduce((a, l) => a + (parseFloat(l.qty) || 0) * l.unit_price, 0);
        },
        onSubmit(e) {
            if (!this.saleId) { e.preventDefault(); alert('Select an invoice.'); return; }
            if (this.total <= 0) { e.preventDefault(); alert('Enter a return quantity for at least one item.'); }
        },
    };
}
</script>
@endpush
@endsection
