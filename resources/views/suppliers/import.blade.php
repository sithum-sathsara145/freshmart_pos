{{-- suppliers/import.blade.php — bulk import suppliers from CSV / Excel --}}
@extends('layouts.app')
@section('title','Import Suppliers')
@section('page-title','Import Suppliers')
@section('content')
<div style="padding:14px 16px;max-width:760px">

@if($result)
{{-- ── Result summary ─────────────────────────────────── --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:14px">
    <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:12px">Import finished</div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
        <div style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:10px 12px">
            <div style="font-size:10px;color:var(--text-3)">Added (new)</div>
            <div style="font-size:22px;font-weight:600;color:var(--success)">{{ $result['created'] }}</div>
        </div>
        <div style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:10px 12px">
            <div style="font-size:10px;color:var(--text-3)">Updated (existing)</div>
            <div style="font-size:22px;font-weight:600;color:var(--info)">{{ $result['updated'] }}</div>
        </div>
        <div style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:10px 12px">
            <div style="font-size:10px;color:var(--text-3)">Skipped (duplicate rows)</div>
            <div style="font-size:22px;font-weight:600;color:var(--warning-2)">{{ $result['skipped'] }}</div>
        </div>
        <div style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:10px 12px">
            <div style="font-size:10px;color:var(--text-3)">Errors</div>
            <div style="font-size:22px;font-weight:600;color:{{ count($result['errors']) ? 'var(--danger)' : 'var(--text-3)' }}">{{ count($result['errors']) }}</div>
        </div>
    </div>

    @if(count($result['errors']))
    <div style="background:var(--danger-soft);border:.5px solid var(--danger-border);border-radius:6px;padding:10px 12px;max-height:220px;overflow:auto">
        <div style="font-size:11px;color:var(--danger-text);font-weight:500;margin-bottom:6px">Rows not imported:</div>
        <ul style="margin:0;padding-left:18px;font-size:11px;color:var(--danger-text)">
            @foreach($result['errors'] as $err)<li style="margin-bottom:2px">{{ $err }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div style="margin-top:14px;display:flex;gap:8px">
        <a href="{{ route('suppliers.index') }}" style="height:34px;padding:0 14px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;display:flex;align-items:center;text-decoration:none">View suppliers</a>
        <a href="{{ route('suppliers.import') }}" style="height:34px;padding:0 14px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;display:flex;align-items:center;text-decoration:none">Import more</a>
    </div>
</div>
@endif

{{-- ── Upload form ────────────────────────────────────── --}}
<form method="POST" action="{{ route('suppliers.import.store') }}" enctype="multipart/form-data">
@csrf
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:14px">
    <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:4px">Upload a file</div>
    <div style="font-size:11px;color:var(--text-3);margin-bottom:14px">
        Accepts <b style="color:var(--text-2)">.csv</b> or <b style="color:var(--text-2)">.xlsx</b> (max 10MB). The first row must be column headers.
        A row whose <b style="color:var(--text-2)">phone</b> (or email, or name) matches an existing supplier
        <b style="color:var(--text-2)">updates</b> it instead of adding a duplicate. Leave a cell blank to keep the current value.
    </div>

    <label for="import-file" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;height:120px;background:var(--bg);border:.5px dashed var(--border);border-radius:8px;cursor:pointer;color:var(--text-3);font-size:12px">
        <i class="ti ti-file-spreadsheet" style="font-size:26px"></i>
        <span id="file-label">Click to choose a CSV or Excel file</span>
    </label>
    <input type="file" name="file" id="import-file" accept=".csv,.xlsx,.txt" required style="display:none"
        onchange="document.getElementById('file-label').textContent = this.files[0] ? this.files[0].name : 'Click to choose a CSV or Excel file'">

    <div style="display:flex;gap:8px;margin-top:14px;align-items:center">
        <button type="submit" style="height:36px;padding:0 20px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px">
            <i class="ti ti-upload" style="font-size:14px"></i>Import suppliers
        </button>
        <a href="{{ route('suppliers.import.sample', ['format' => 'csv']) }}" style="height:36px;padding:0 14px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none">
            <i class="ti ti-file-text" style="font-size:13px"></i>Sample CSV
        </a>
        <a href="{{ route('suppliers.import.sample', ['format' => 'xlsx']) }}" style="height:36px;padding:0 14px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none">
            <i class="ti ti-file-spreadsheet" style="font-size:13px"></i>Sample Excel
        </a>
    </div>
</div>
</form>

{{-- ── Column reference ───────────────────────────────── --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:8px">Columns</div>
    <div style="font-size:11px;color:var(--text-3);line-height:1.9">
        <div><b style="color:var(--text-hi)">Required:</b> name</div>
        <div><b style="color:var(--text-hi)">Optional:</b> contact_person, phone, email, address, city</div>
        <div style="margin-top:6px;color:var(--text-2)">
            • Header names are case-insensitive; extra columns are ignored.
            • <b>Total purchases</b> and <b>balance due</b> cannot be imported — they are worked out from the supplier's purchase records.
        </div>
    </div>
</div>

</div>
@endsection
