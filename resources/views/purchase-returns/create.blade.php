{{-- purchase-returns/create.blade.php --}}
@extends('layouts.app')
@section('title','New Purchase Return')
@section('page-title','New Purchase Return / Dr. Note')
@section('content')
@php $inp = 'background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none'; @endphp
<div style="padding:14px 16px;max-width:720px" x-data="drForm(@js($purchases))">
<form method="POST" action="{{ route('purchase-returns.store') }}" @submit="onSubmit($event)">
@csrf
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Dr. Note details</div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Original purchase bill *</label>
        <select name="purchase_id" required x-model="purchaseId" @change="pick()" style="{{ $inp }};width:100%;box-sizing:border-box">
            <option value="">— Select bill —</option>
            <template x-for="p in purchases" :key="p.id">
                <option :value="p.id" x-text="p.bill_no + ' — ' + p.supplier + ' — Rs. ' + p.total.toLocaleString()"></option>
            </template>
        </select>
        <div x-show="purchases.length === 0" style="font-size:11px;color:#64748b;margin-top:5px">No bills with returnable stock on hand.</div>
    </div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Return reason *</label>
        <select name="reason" required style="{{ $inp }};width:100%;box-sizing:border-box">
            <option value="">— Select reason —</option>
            @foreach(['Damaged items received','Wrong items delivered','Expired products','Excess quantity','Quality issue','Other'] as $r)
            <option value="{{ $r }}" {{ old('reason')===$r?'selected':'' }}>{{ $r }}</option>
            @endforeach
        </select>
    </div>

    {{-- Lines of the selected bill --}}
    <div x-show="lines.length" x-cloak style="margin-top:6px">
        <div style="font-size:12px;color:#64748b;margin-bottom:6px">Items to return to supplier</div>
        <div style="display:grid;grid-template-columns:2.2fr .8fr .9fr 1fr 1fr;gap:6px;font-size:10px;color:#4a5568;padding:0 2px 4px">
            <span>Product</span><span style="text-align:center">Bought</span><span style="text-align:center">On hand</span><span>Return qty</span><span style="text-align:right">Line credit</span>
        </div>
        <template x-for="(l,i) in lines" :key="l.purchase_item_id">
            <div style="display:grid;grid-template-columns:2.2fr .8fr .9fr 1fr 1fr;gap:6px;align-items:center;margin-bottom:6px">
                <input type="hidden" :name="`items[${i}][purchase_item_id]`" :value="l.purchase_item_id">
                <div style="font-size:11px;color:#e2e8f0;white-space:nowrap;text-overflow:ellipsis;overflow:hidden" x-text="l.name"></div>
                <div style="font-size:11px;color:#64748b;text-align:center" x-text="l.purchased"></div>
                <div style="font-size:11px;color:#94a3b8;text-align:center" x-text="l.remaining"></div>
                <input type="number" :name="`items[${i}][quantity]`" x-model.number="l.qty"
                    min="0" :max="l.remaining" step="0.001" @input="clamp(l)"
                    style="{{ $inp }};padding:5px 7px;font-size:11px;width:100%;box-sizing:border-box">
                <div style="font-size:11px;color:#f87171;text-align:right" x-text="'Rs. ' + ((l.qty||0)*l.unit_price).toFixed(2)"></div>
            </div>
        </template>
    </div>
    <div x-show="purchaseId && !lines.length" x-cloak style="font-size:11px;color:#64748b;padding:6px 2px">This bill has no stock left to return.</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px;padding-top:12px;border-top:.5px solid #2a2d3a">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Total credit (Rs.)</label>
            <div style="{{ $inp }};width:100%;box-sizing:border-box;color:#f87171;font-weight:500" x-text="'Rs. ' + total.toFixed(2)"></div>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Credit method *</label>
            <select name="credit_method" required style="{{ $inp }};width:100%;box-sizing:border-box">
                <option value="credit_note">Credit note (reduce payable)</option>
                <option value="cash_refund">Cash refund</option>
                <option value="replacement">Replacement</option>
            </select>
        </div>
    </div>
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('purchase-returns.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#7f1d1d;color:#fca5a5;border:.5px solid #991b1b;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-arrow-back-up" style="font-size:13px;margin-right:4px"></i>Process Dr. Note</button>
</div>
</form>
</div>

@push('scripts')
<script>
function drForm(purchases) {
    return {
        purchases: purchases || [],
        purchaseId: '',
        lines: [],
        pick() {
            const p = this.purchases.find(x => String(x.id) === String(this.purchaseId));
            this.lines = p ? p.lines.map(l => ({ ...l, qty: 0 })) : [];
        },
        clamp(l) {
            let q = parseFloat(l.qty) || 0;
            if (q < 0) q = 0;
            if (q > l.remaining) q = l.remaining;
            l.qty = q;
        },
        get total() {
            return this.lines.reduce((a, l) => a + (parseFloat(l.qty) || 0) * l.unit_price, 0);
        },
        onSubmit(e) {
            if (!this.purchaseId) { e.preventDefault(); alert('Select a bill.'); return; }
            if (this.total <= 0) { e.preventDefault(); alert('Enter a return quantity for at least one item.'); }
        },
    };
}
</script>
@endpush
@endsection
