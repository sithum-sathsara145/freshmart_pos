{{-- products/import.blade.php — bulk import products from CSV / Excel --}}
@extends('layouts.app')
@section('title','Import Products')
@section('page-title','Import Products')
@section('content')
<div style="padding:14px 16px;max-width:760px">

@if($result)
{{-- ── Result summary ─────────────────────────────────── --}}
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:14px">
    <div style="font-size:13px;font-weight:600;color:#e2e8f0;margin-bottom:12px">Import finished</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">
        <div style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:10px 12px">
            <div style="font-size:10px;color:#64748b">Imported</div>
            <div style="font-size:22px;font-weight:600;color:#4ade80">{{ $result['created'] }}</div>
        </div>
        <div style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:10px 12px">
            <div style="font-size:10px;color:#64748b">Skipped (already exist)</div>
            <div style="font-size:22px;font-weight:600;color:#fbbf24">{{ $result['skipped'] }}</div>
        </div>
        <div style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:10px 12px">
            <div style="font-size:10px;color:#64748b">Errors</div>
            <div style="font-size:22px;font-weight:600;color:{{ count($result['errors']) ? '#f87171' : '#64748b' }}">{{ count($result['errors']) }}</div>
        </div>
    </div>

    @if(count($result['errors']))
    <div style="background:#7f1d1d33;border:.5px solid #991b1b;border-radius:6px;padding:10px 12px;max-height:220px;overflow:auto">
        <div style="font-size:11px;color:#fca5a5;font-weight:500;margin-bottom:6px">Rows not imported:</div>
        <ul style="margin:0;padding-left:18px;font-size:11px;color:#fca5a5">
            @foreach($result['errors'] as $err)<li style="margin-bottom:2px">{{ $err }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div style="margin-top:14px;display:flex;gap:8px">
        <a href="{{ route('products.index') }}" style="height:34px;padding:0 14px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;display:flex;align-items:center;text-decoration:none">View products</a>
        <a href="{{ route('products.import') }}" style="height:34px;padding:0 14px;background:#1e2130;color:#94a3b8;border:.5px solid #2a2d3a;border-radius:6px;font-size:12px;display:flex;align-items:center;text-decoration:none">Import more</a>
    </div>
</div>
@endif

{{-- ── Upload form ────────────────────────────────────── --}}
<form method="POST" action="{{ route('products.import.store') }}" enctype="multipart/form-data">
@csrf
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:14px">
    <div style="font-size:13px;font-weight:600;color:#e2e8f0;margin-bottom:4px">Upload a file</div>
    <div style="font-size:11px;color:#64748b;margin-bottom:14px">
        Accepts <b style="color:#94a3b8">.csv</b> or <b style="color:#94a3b8">.xlsx</b> (max 10MB). The first row must be column headers.
        Products whose SKU or barcode already exists are skipped.
    </div>

    <label for="import-file" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;height:120px;background:#0f1117;border:.5px dashed #2a2d3a;border-radius:8px;cursor:pointer;color:#64748b;font-size:12px">
        <i class="ti ti-file-spreadsheet" style="font-size:26px"></i>
        <span id="file-label">Click to choose a CSV or Excel file</span>
    </label>
    <input type="file" name="file" id="import-file" accept=".csv,.xlsx,.txt" required style="display:none"
        onchange="document.getElementById('file-label').textContent = this.files[0] ? this.files[0].name : 'Click to choose a CSV or Excel file'">

    <div style="display:flex;gap:8px;margin-top:14px;align-items:center">
        <button type="submit" style="height:36px;padding:0 20px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px">
            <i class="ti ti-upload" style="font-size:14px"></i>Import products
        </button>
        <a href="{{ route('products.import.sample', ['format' => 'csv']) }}" style="height:36px;padding:0 14px;background:#1e2130;color:#94a3b8;border:.5px solid #2a2d3a;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none">
            <i class="ti ti-file-text" style="font-size:13px"></i>Sample CSV
        </a>
        <a href="{{ route('products.import.sample', ['format' => 'xlsx']) }}" style="height:36px;padding:0 14px;background:#1e2130;color:#94a3b8;border:.5px solid #2a2d3a;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none">
            <i class="ti ti-file-spreadsheet" style="font-size:13px"></i>Sample Excel
        </a>
    </div>
</div>
</form>

{{-- ── Column reference ───────────────────────────────── --}}
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:8px">Columns</div>
    <div style="font-size:11px;color:#64748b;line-height:1.9">
        <div><b style="color:#cbd5e1">Required:</b> name</div>
        <div><b style="color:#cbd5e1">Optional:</b> sku (6 digits, auto if blank), barcode (auto if blank), category, brand, unit, is_weighed (0/1), scale_plu, purchase_price, sale_price, tax_percent, discount_percent, min_stock, opening_stock, description, status (active/inactive), show_in_online_store (0/1), image_url</div>
        <div style="margin-top:6px;color:#94a3b8">• Unknown <b>category</b>/<b>brand</b> names are created automatically. • Header names are case-insensitive; extra columns are ignored.</div>
    </div>
</div>

</div>
@endsection
