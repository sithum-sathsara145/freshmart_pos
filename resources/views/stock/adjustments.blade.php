{{-- stock/adjustments.blade.php --}}
@extends('layouts.app')
@section('title','Stock Adjustments')
@section('page-title','Stock Adjustments')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:360px 1fr;gap:14px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;align-self:start">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">New stock adjustment</div>
    <form method="POST" action="{{ route('stock.adjustments.store') }}">
    @csrf
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Product *</label>
        <select name="product_id" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="">— Select product —</option>
            @foreach($products as $p)<option value="{{ $p->id }}" {{ old('product_id')==$p->id?'selected':'' }}>{{ $p->name }} ({{ $p->sku }})</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Adjustment type *</label>
        <select name="type" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="add">Add stock (+)</option>
            <option value="remove">Remove stock (-)</option>
            <option value="damage">Damage / Loss</option>
            <option value="expired">Expired</option>
            <option value="set">Set exact quantity</option>
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Quantity *</label>
        <input type="number" name="quantity" min="0.001" step="0.001" required placeholder="0" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="margin-bottom:12px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Reason</label>
        <input type="text" name="reason" placeholder="Reason for adjustment" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    <button type="submit" style="width:100%;height:36px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-adjustments" style="font-size:13px;margin-right:4px"></i>Save Adjustment</button>
    </form>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Recent adjustments</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:7px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Product</th>
            <th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">Type</th>
            <th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">Qty</th>
            <th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">Reason</th>
            <th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">By</th>
            <th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">Date</th>
        </tr></thead>
        <tbody>
        @forelse($adjustments as $a)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px 10px;color:#e2e8f0;font-weight:500">{{ $a->product?->name }}</td>
            <td style="padding:7px 10px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ in_array($a->type,['damage','expired','remove'])?'#7f1d1d':'#14532d' }};color:{{ in_array($a->type,['damage','expired','remove'])?'#fca5a5':'#4ade80' }}">{{ ucfirst($a->type) }}</span></td>
            <td style="padding:7px 10px;color:{{ in_array($a->type,['add','set'])?'#4ade80':'#f87171' }};font-weight:500">{{ in_array($a->type,['damage','expired','remove'])?'-':'+' }}{{ $a->quantity }}</td>
            <td style="padding:7px 10px;color:#64748b;font-size:11px">{{ $a->reason ?? '—' }}</td>
            <td style="padding:7px 10px;color:#94a3b8">{{ $a->createdBy?->name ?? '—' }}</td>
            <td style="padding:7px 10px;color:#64748b">{{ $a->created_at->format('d M H:i') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="padding:24px;text-align:center;color:#4a5568">No adjustments yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:10px">{{ $adjustments->links() }}</div>
</div>
</div>
</div>
@endsection
