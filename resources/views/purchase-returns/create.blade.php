{{-- purchase-returns/create.blade.php --}}
@extends('layouts.app')
@section('title','New Purchase Return')
@section('page-title','New Purchase Return / Dr. Note')
@section('content')
<div style="padding:14px 16px;max-width:580px">
<form method="POST" action="{{ route('purchase-returns.store') }}">
@csrf
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Dr. Note details</div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Original purchase bill *</label>
        <select name="purchase_id" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="">— Select bill —</option>
            @foreach($purchases as $p)
            <option value="{{ $p->id }}" {{ old('purchase_id')==$p->id?'selected':'' }}>
                {{ $p->bill_no }} — {{ $p->supplier?->name }} — Rs. {{ number_format($p->total) }}
            </option>
            @endforeach
        </select>
    </div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Return reason *</label>
        <select name="reason" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="">— Select reason —</option>
            @foreach(['Damaged items received','Wrong items delivered','Expired products','Excess quantity','Quality issue','Other'] as $r)
            <option value="{{ $r }}" {{ old('reason')===$r?'selected':'' }}>{{ $r }}</option>
            @endforeach
        </select>
    </div>

    <div style="font-size:12px;color:#64748b;margin-bottom:8px;margin-top:4px">Return items</div>
    <div id="pr-items">
        <div class="pr-row" style="display:grid;grid-template-columns:2fr 1fr 1fr 28px;gap:6px;margin-bottom:6px">
            <input type="text" name="items[0][product_id]" placeholder="Product name / ID"
                style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][quantity]" placeholder="Qty" min="0.001" step="0.001"
                style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <input type="number" name="items[0][unit_price]" placeholder="Unit price" min="0" step="0.01"
                style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <div style="width:28px;height:28px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#f87171" onclick="this.parentElement.remove()">
                <i class="ti ti-x" style="font-size:12px"></i>
            </div>
        </div>
    </div>
    <button type="button" onclick="addPRRow()" style="height:28px;padding:0 10px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;color:#94a3b8;font-size:11px;cursor:pointer;margin-top:4px">
        <i class="ti ti-plus" style="font-size:11px"></i> Add item
    </button>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Total return amount (Rs.) *</label>
            <input type="number" name="return_amount" value="{{ old('return_amount') }}" step="0.01" min="0.01" required
                style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Credit method</label>
            <select name="credit_method" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
                <option value="credit_note">Credit note</option>
                <option value="cash_refund">Cash refund</option>
                <option value="replacement">Replacement</option>
            </select>
        </div>
    </div>
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('purchase-returns.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#7f1d1d;color:#fca5a5;border:.5px solid #991b1b;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">
        <i class="ti ti-arrow-back-up" style="font-size:13px;margin-right:4px"></i>Process Dr. Note
    </button>
</div>
</form>
</div>

@push('scripts')
<script>
let pri = 1;
function addPRRow() {
    const d = document.createElement('div');
    d.className = 'pr-row';
    d.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr 28px;gap:6px;margin-bottom:6px';
    d.innerHTML = `<input type="text" name="items[${pri}][product_id]" placeholder="Product name / ID" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none"><input type="number" name="items[${pri}][quantity]" placeholder="Qty" min="0.001" step="0.001" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none"><input type="number" name="items[${pri}][unit_price]" placeholder="Unit price" min="0" step="0.01" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none"><div style="width:28px;height:28px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#f87171" onclick="this.parentElement.remove()"><i class="ti ti-x" style="font-size:12px"></i></div>`;
    document.getElementById('pr-items').appendChild(d);
    pri++;
}
</script>
@endpush
@endsection
