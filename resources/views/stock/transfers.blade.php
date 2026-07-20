{{-- stock/transfers.blade.php --}}
@extends('layouts.app')
@section('title','Stock Transfers')
@section('page-title','Stock Transfers')
@section('content')
<div style="padding:14px 16px;display:grid;grid-template-columns:340px 1fr;gap:14px">
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;align-self:start">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">New transfer</div>
    <form method="POST" action="{{ route('stock.transfers.store') }}">
    @csrf
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">From branch *</label>
        <select name="from_branch_id" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">To branch *</label>
        <select name="to_branch_id" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Product *</label>
        <select name="product_id" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            <option value="">— Select product —</option>
            @foreach($products as $p)<option value="{{ $p->id }}" {{ old('product_id')==$p->id?'selected':'' }}>{{ $p->name }} ({{ $p->sku }})</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Quantity *</label>
        <input type="number" name="quantity" min="0.001" step="0.001" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="margin-bottom:12px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Notes</label>
        <input type="text" name="notes" placeholder="Optional" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    </div>
    <button type="submit" style="width:100%;height:36px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-arrows-exchange" style="font-size:13px;margin-right:4px"></i>Create Transfer</button>
    </form>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Transfer history</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            <th style="padding:7px 10px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Product</th>
            <th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">From</th>
            <th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">To</th>
            <th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">Qty</th>
            <th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">Date</th>
            <th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">Status</th>
        </tr></thead>
        <tbody>
        @forelse($transfers as $t)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:7px 10px;color:var(--text);font-weight:500">{{ $t->product?->name }}</td>
            <td style="padding:7px 10px;color:var(--text-3)">{{ $t->fromBranch?->name }}</td>
            <td style="padding:7px 10px;color:var(--text-3)">{{ $t->toBranch?->name }}</td>
            <td style="padding:7px 10px;color:var(--text)">{{ $t->quantity }}</td>
            <td style="padding:7px 10px;color:var(--text-3)">{{ $t->created_at->format('d M Y') }}</td>
            <td style="padding:7px 10px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $t->status==='completed'?'var(--success-soft)':'var(--warning-soft)' }};color:{{ $t->status==='completed'?'var(--success)':'var(--warning)' }}">{{ ucfirst($t->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-4)">No transfers yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:10px">{{ $transfers->links() }}</div>
</div>
</div>
@endsection
