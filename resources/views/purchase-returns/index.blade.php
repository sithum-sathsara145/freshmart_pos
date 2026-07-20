{{-- purchase-returns/index.blade.php --}}
@extends('layouts.app')
@section('title','Purchase Returns')
@section('page-title','Purchase Returns / Dr. Notes')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <a href="{{ route('purchase-returns.create') }}" style="height:34px;padding:0 14px;background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>New Dr. Note
    </a>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Dr. Note #','Supplier','Ref Bill','Date','Amount','Reason','Status','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($returns as $r)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--danger);font-weight:500">{{ $r->dr_note_no }}</td>
        <td style="padding:9px 12px;color:var(--text)">{{ $r->purchase?->supplier?->name }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $r->purchase?->bill_no }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $r->created_at->format('d M Y') }}</td>
        <td style="padding:9px 12px;color:var(--danger);font-weight:500">Rs. {{ number_format($r->return_amount) }}</td>
        <td style="padding:9px 12px;color:var(--text-2);font-size:11px">{{ Str::limit($r->reason,25) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $r->status==='credited'?'var(--success-soft)':'var(--warning-soft)' }};color:{{ $r->status==='credited'?'var(--success)':'var(--warning)' }}">{{ ucfirst($r->status) }}</span></td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:5px">
                <a href="{{ route('purchase-returns.show',$r) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                <form method="POST" action="{{ route('purchase-returns.destroy',$r) }}" onsubmit="return confirm('Reverse Dr. Note {{ $r->dr_note_no }}? Stock and any credit/refund will be undone.');" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Reverse" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--danger);cursor:pointer"><i class="ti ti-arrow-back-up" style="font-size:12px"></i></button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:32px;text-align:center;color:var(--text-4)">No purchase returns found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $returns->links() }}</div>
</div>
@endsection
