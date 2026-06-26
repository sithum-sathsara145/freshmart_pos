{{-- stock/transfers.blade.php --}}
@extends('layouts.app')
@section('title','Stock Transfers')
@section('page-title','Stock Transfers')
@section('content')
<div style="padding:14px 16px;display:grid;grid-template-columns:340px 1fr;gap:14px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;align-self:start">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">New transfer</div>
    <form method="POST" action="{{ route('stock.transfers.store') }}">
    @csrf
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">From branch *</label>
        <select name="from_branch_id" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">To branch *</label>
        <select name="to_branch_id" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
        </select>
    </div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Product *</label>
        <input type="text" name="product_id" placeholder="Product ID or name" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Quantity *</label>
        <input type="number" name="quantity" min="0.001" step="0.001" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="margin-bottom:12px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Notes</label>
        <input type="text" name="notes" placeholder="Optional" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    <button type="submit" style="width:100%;height:36px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-arrows-exchange" style="font-size:13px;margin-right:4px"></i>Create Transfer</button>
    </form>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Transfer history</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:7px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Product</th>
            <th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">From</th>
            <th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">To</th>
            <th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">Qty</th>
            <th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">Date</th>
            <th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">Status</th>
        </tr></thead>
        <tbody>
        @forelse($transfers as $t)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px 10px;color:#e2e8f0;font-weight:500">{{ $t->product?->name }}</td>
            <td style="padding:7px 10px;color:#64748b">{{ $t->fromBranch?->city }}</td>
            <td style="padding:7px 10px;color:#64748b">{{ $t->toBranch?->city }}</td>
            <td style="padding:7px 10px;color:#e2e8f0">{{ $t->quantity }}</td>
            <td style="padding:7px 10px;color:#64748b">{{ $t->created_at->format('d M Y') }}</td>
            <td style="padding:7px 10px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $t->status==='completed'?'#14532d':'#451a03' }};color:{{ $t->status==='completed'?'#4ade80':'#fb923c' }}">{{ ucfirst($t->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="6" style="padding:24px;text-align:center;color:#4a5568">No transfers yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:10px">{{ $transfers->links() }}</div>
</div>
</div>
@endsection
