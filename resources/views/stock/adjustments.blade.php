{{-- stock/adjustments.blade.php --}}
@extends('layouts.app')
@section('title','Stock Adjustments')
@section('page-title','Stock Adjustments')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:360px 1fr;gap:14px">
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;align-self:start">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">New stock adjustment</div>
    <form method="POST" action="{{ route('stock.adjustments.store') }}">
    @csrf
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Product *</label>
        <select name="product_id" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            <option value="">— Select product —</option>
            @foreach($products as $p)<option value="{{ $p->id }}" {{ old('product_id')==$p->id?'selected':'' }}>{{ $p->name }} ({{ $p->sku }})</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Adjustment type *</label>
        <select name="type" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            <option value="add">Add stock (+)</option>
            <option value="remove">Remove stock (-)</option>
            <option value="damage">Damage / Loss</option>
            <option value="expired">Expired</option>
            <option value="set">Set exact quantity</option>
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Quantity *</label>
        <input type="number" name="quantity" min="0.001" step="0.001" required placeholder="0" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="margin-bottom:12px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Reason</label>
        <input type="text" name="reason" placeholder="Reason for adjustment" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    </div>
    <button type="submit" style="width:100%;height:36px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-adjustments" style="font-size:13px;margin-right:4px"></i>Save Adjustment</button>
    </form>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Recent adjustments</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            <th style="padding:7px 10px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Product</th>
            <th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">Type</th>
            <th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">Qty</th>
            <th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">Reason</th>
            <th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">By</th>
            <th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">Date</th>
        </tr></thead>
        <tbody>
        @forelse($adjustments as $a)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:7px 10px;color:var(--text);font-weight:500">{{ $a->product?->name }}</td>
            <td style="padding:7px 10px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ in_array($a->type,['damage','expired','remove'])?'var(--danger-soft)':'var(--success-soft)' }};color:{{ in_array($a->type,['damage','expired','remove'])?'var(--danger-text)':'var(--success)' }}">{{ ucfirst($a->type) }}</span></td>
            <td style="padding:7px 10px;color:{{ in_array($a->type,['add','set'])?'var(--success)':'var(--danger)' }};font-weight:500">{{ in_array($a->type,['damage','expired','remove'])?'-':'+' }}{{ $a->quantity }}</td>
            <td style="padding:7px 10px;color:var(--text-3);font-size:11px">{{ $a->reason ?? '—' }}</td>
            <td style="padding:7px 10px;color:var(--text-2)">{{ $a->createdBy?->name ?? '—' }}</td>
            <td style="padding:7px 10px;color:var(--text-3)">{{ $a->created_at->format('d M H:i') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-4)">No adjustments yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:10px">{{ $adjustments->links() }}</div>
</div>
</div>
</div>
@endsection
