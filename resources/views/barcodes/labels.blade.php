{{-- barcodes/labels.blade.php — pick products and print their barcodes as labels --}}
@extends('layouts.app')
@section('title','Barcode Labels')
@section('page-title','Barcode Labels')
@section('content')
@php
    $inp = 'background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none';
    $lbl = 'display:block;font-size:11px;color:var(--text-3);margin-bottom:4px';
@endphp
<div style="padding:14px 16px" x-data="labelPicker()">
<form method="POST" action="{{ route('barcodes.bulk') }}" target="_blank" @submit="onSubmit($event)">
@csrf
<input type="hidden" name="default_copies" :value="defaultCopies">

{{-- Controls --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:12px;margin-bottom:12px;display:flex;flex-wrap:wrap;gap:12px;align-items:end">
    <div style="flex:1;min-width:200px">
        <label style="{{ $lbl }}">Search</label>
        <input type="text" x-model="search" placeholder="Name, SKU or barcode…" style="{{ $inp }};width:100%">
    </div>
    <div style="min-width:150px">
        <label style="{{ $lbl }}">Category</label>
        <select x-model="category" style="{{ $inp }};width:100%">
            <option value="">All categories</option>
            @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div style="min-width:160px">
        <label style="{{ $lbl }}">Label size</label>
        <select name="label_size" style="{{ $inp }};width:100%">
            <option value="a4">A4 sheet (grid)</option>
            <option value="roll58">Thermal roll 58mm</option>
            <option value="roll40">Thermal roll 40mm</option>
        </select>
    </div>
    <div style="width:120px">
        <label style="{{ $lbl }}">Copies each</label>
        <div style="display:flex;gap:5px">
            <input type="number" x-model.number="defaultCopies" min="1" max="200" style="{{ $inp }};width:64px">
            <button type="button" @click="applyCopies()" title="Apply to all rows"
                style="background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--primary-text);font-size:11px;padding:0 8px;cursor:pointer">Set all</button>
        </div>
    </div>
    <div style="display:flex;gap:14px;padding-bottom:7px">
        <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text-2);cursor:pointer">
            <input type="checkbox" name="show_name" value="1" checked style="accent-color:var(--primary)">Name</label>
        <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text-2);cursor:pointer">
            <input type="checkbox" name="show_price" value="1" checked style="accent-color:var(--primary)">Price</label>
    </div>
</div>

{{-- Action bar --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
    <div style="font-size:12px;color:var(--text-2)"><b x-text="selectedCount" style="color:var(--text)"></b> product(s) selected</div>
    <button type="submit" :style="selectedCount===0 ? 'opacity:.5;cursor:not-allowed' : ''"
        style="height:36px;padding:0 20px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px">
        <i class="ti ti-printer" style="font-size:14px"></i>Generate labels
    </button>
</div>

{{-- Product table --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead>
            <tr style="border-bottom:.5px solid var(--border);background:var(--sunken)">
                <th style="padding:9px 12px;width:36px;text-align:center"><input type="checkbox" @change="toggleAll($event)" style="accent-color:var(--primary)" title="Select all visible"></th>
                <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Product</th>
                <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">SKU</th>
                <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Barcode</th>
                <th style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Price</th>
                <th style="padding:9px 12px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px;width:90px">Copies</th>
            </tr>
        </thead>
        <tbody>
        @foreach($products as $p)
            <tr data-row
                data-name="{{ strtolower($p->name) }}" data-sku="{{ $p->sku }}" data-barcode="{{ $p->barcode }}" data-cat="{{ $p->category_id }}"
                x-show="visible($el)"
                style="border-bottom:.5px solid var(--surface-3)">
                <td style="padding:8px 12px;text-align:center">
                    <input type="checkbox" name="product_ids[]" value="{{ $p->id }}" @change="recount()" style="accent-color:var(--primary)">
                </td>
                <td style="padding:8px 12px;color:var(--text);font-weight:500">{{ $p->name }}</td>
                <td style="padding:8px 12px;color:var(--text-2);font-family:monospace">{{ $p->sku }}</td>
                <td style="padding:8px 12px;color:var(--text-3);font-family:monospace;font-size:10px">{{ $p->barcode ?: '—' }}</td>
                <td style="padding:8px 12px;color:var(--success);text-align:right">Rs. {{ number_format($p->sale_price) }}</td>
                <td style="padding:8px 12px;text-align:center">
                    <input type="number" name="copies[{{ $p->id }}]" value="1" min="1" max="200" data-copies
                        style="{{ $inp }};width:64px;text-align:center;padding:5px">
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($products->isEmpty())
    <div style="padding:24px;text-align:center;color:var(--text-3);font-size:12px">No products yet.</div>
    @endif
</div>
</form>
</div>

@push('scripts')
<script>
function labelPicker() {
    return {
        search: '', category: '', defaultCopies: 1, selectedCount: 0,

        // x-show per row — reads the row's data-* so product names with quotes are safe.
        visible(el) {
            const q = this.search.trim().toLowerCase();
            const hay = (el.dataset.name || '') + ' ' + (el.dataset.sku || '') + ' ' + (el.dataset.barcode || '');
            const okQ = !q || hay.includes(q);
            const okC = !this.category || el.dataset.cat === this.category;
            return okQ && okC;
        },
        recount() {
            this.selectedCount = document.querySelectorAll('input[name="product_ids[]"]:checked').length;
        },
        toggleAll(e) {
            document.querySelectorAll('tr[data-row]').forEach(tr => {
                if (tr.offsetParent !== null) {                 // visible rows only
                    const cb = tr.querySelector('input[name="product_ids[]"]');
                    if (cb) cb.checked = e.target.checked;
                }
            });
            this.recount();
        },
        applyCopies() {
            const n = this.defaultCopies || 1;
            document.querySelectorAll('input[data-copies]').forEach(i => i.value = n);
        },
        onSubmit(e) {
            if (this.selectedCount === 0) { e.preventDefault(); alert('Select at least one product to print.'); }
        },
    };
}
</script>
@endpush
@endsection
