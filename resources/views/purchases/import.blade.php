{{-- purchases/import.blade.php — turn a supplier's invoice into a purchase + stock --}}
@extends('layouts.app')
@section('title','Import Received Goods')
@section('page-title','Import Received Goods')
@section('content')
<div style="padding:14px 16px;max-width:760px">

@if($result && $result['ok'])
{{-- ── Posted ─────────────────────────────────────────── --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:14px">
    <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:12px">
        Stock received — purchase #{{ $result['purchase']->bill_no }} created
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">
        <div style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:10px 12px">
            <div style="font-size:10px;color:var(--text-3)">Product lines</div>
            <div style="font-size:22px;font-weight:600;color:var(--success)">{{ $result['lines'] }}</div>
        </div>
        <div style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:10px 12px">
            <div style="font-size:10px;color:var(--text-3)">Units added</div>
            <div style="font-size:22px;font-weight:600;color:var(--info)">{{ rtrim(rtrim(number_format($result['units'], 3), '0'), '.') }}</div>
        </div>
        <div style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:10px 12px">
            <div style="font-size:10px;color:var(--text-3)">Owed to supplier</div>
            <div style="font-size:22px;font-weight:600;color:var(--warning)">Rs. {{ number_format($result['total'], 2) }}</div>
        </div>
    </div>
    <div style="font-size:11px;color:var(--text-3);margin-bottom:12px">
        The bill is recorded as <b style="color:var(--text-2)">unpaid</b> — settle it from Payment Out when you pay the supplier.
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('purchases.show', $result['purchase']) }}" style="height:34px;padding:0 14px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;display:flex;align-items:center;text-decoration:none">View purchase</a>
        <a href="{{ route('purchases.import') }}" style="height:34px;padding:0 14px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;display:flex;align-items:center;text-decoration:none">Import another</a>
    </div>
</div>
@endif

@if($result && ! $result['ok'])
{{-- ── Rejected: nothing was written ──────────────────── --}}
<div style="background:var(--danger-soft);border:.5px solid var(--danger-border);border-radius:8px;padding:16px;margin-bottom:14px">
    <div style="font-size:13px;font-weight:600;color:var(--danger-text);margin-bottom:6px">
        <i class="ti ti-alert-triangle" style="font-size:15px;vertical-align:-2px"></i>
        Nothing was imported
    </div>
    <div style="font-size:11px;color:var(--danger-text);margin-bottom:10px">
        A goods-received note has to match the supplier's invoice exactly, so the whole file is rejected
        if any line is wrong. Fix these {{ count($result['errors']) }} row(s) and upload it again —
        {{ $result['lines'] }} other line(s) were fine.
    </div>
    <ul style="margin:0;padding-left:18px;font-size:11px;color:var(--danger-text);max-height:240px;overflow:auto">
        @foreach($result['errors'] as $err)<li style="margin-bottom:2px">{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

{{-- ── Upload form ────────────────────────────────────── --}}
<form method="POST" action="{{ route('purchases.import.store') }}" enctype="multipart/form-data">
@csrf
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:14px">
    <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:4px">Who delivered it?</div>
    <div style="font-size:11px;color:var(--text-3);margin-bottom:14px">
        Every line in the file is added to stock at the cost you were charged, and the total is put on this supplier's balance.
    </div>

    <div style="display:grid;grid-template-columns:1fr 180px;gap:10px;margin-bottom:14px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Supplier</label>
            <select name="supplier_id" required
                style="width:100%;height:36px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:0 9px;outline:none">
                <option value="">Choose a supplier…</option>
                @foreach($suppliers as $s)
                <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Invoice date</label>
            <input type="date" name="purchase_date" required value="{{ old('purchase_date', date('Y-m-d')) }}"
                style="width:100%;height:36px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:0 9px;outline:none">
        </div>
    </div>

    <div style="margin-bottom:14px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:5px">Notes (optional)</label>
        <input type="text" name="notes" value="{{ old('notes') }}" placeholder="e.g. Invoice INV-4471"
            style="width:100%;height:36px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:0 9px;outline:none">
    </div>

    <label for="import-file" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;height:120px;background:var(--bg);border:.5px dashed var(--border);border-radius:8px;cursor:pointer;color:var(--text-3);font-size:12px">
        <i class="ti ti-file-spreadsheet" style="font-size:26px"></i>
        <span id="file-label">Click to choose a CSV or Excel file</span>
    </label>
    <input type="file" name="file" id="import-file" accept=".csv,.xlsx,.txt" required style="display:none"
        onchange="document.getElementById('file-label').textContent = this.files[0] ? this.files[0].name : 'Click to choose a CSV or Excel file'">

    <div style="display:flex;gap:8px;margin-top:14px;align-items:center">
        <button type="submit" style="height:36px;padding:0 20px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px">
            <i class="ti ti-package-import" style="font-size:14px"></i>Receive stock
        </button>
        <a href="{{ route('purchases.import.sample', ['format' => 'csv']) }}" style="height:36px;padding:0 14px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none">
            <i class="ti ti-file-text" style="font-size:13px"></i>Sample CSV
        </a>
        <a href="{{ route('purchases.import.sample', ['format' => 'xlsx']) }}" style="height:36px;padding:0 14px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none">
            <i class="ti ti-file-spreadsheet" style="font-size:13px"></i>Sample Excel
        </a>
    </div>
</div>
</form>

{{-- ── Column reference ───────────────────────────────── --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:8px">Columns</div>
    <div style="font-size:11px;color:var(--text-3);line-height:1.9">
        <div><b style="color:var(--text-hi)">Required:</b> quantity, unit_price (what the supplier charged you per unit), and one of barcode / sku / name to identify the product</div>
        <div><b style="color:var(--text-hi)">Optional:</b> batch_no, mrp, sale_price (sets the new selling price)</div>
        <div style="margin-top:6px;color:var(--text-2)">
            • Products are matched, never created — anything unknown has to be added under Products first.
            • Leave <b>sale_price</b> blank to keep the current selling price.
        </div>
    </div>
</div>

</div>
@endsection
