{{-- quotations/index.blade.php --}}
@extends('layouts.app')
@section('title','Quotations')
@section('page-title','Quotations / Estimates')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Total quotes</div><div style="font-size:18px;font-weight:500;color:var(--text)">{{ $stats['total'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Pending</div><div style="font-size:18px;font-weight:500;color:var(--warning)">{{ $stats['pending'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Converted</div><div style="font-size:18px;font-weight:500;color:var(--success)">{{ $stats['converted'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Conversion rate</div><div style="font-size:18px;font-weight:500;color:var(--primary-text)">{{ $stats['total'] > 0 ? round($stats['converted']/$stats['total']*100) : 0 }}%</div></div>
</div>
<div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    @can('quotations.create')
    <a href="{{ route('quotations.create') }}" style="height:34px;padding:0 14px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>New Quotation
    </a>
    @endcan
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Quote #','Customer','Date','Valid till','Total','Status','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($quotations as $q)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--info);font-weight:500">{{ $q->quote_no }}</td>
        <td style="padding:9px 12px;color:var(--text)">{{ $q->customer?->name ?? 'Walk-in' }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $q->created_at->format('d M Y') }}</td>
        <td style="padding:9px 12px;color:{{ $q->valid_till && \Carbon\Carbon::parse($q->valid_till)->isPast() ? 'var(--danger)':'var(--text-3)' }}">{{ $q->valid_till ? \Carbon\Carbon::parse($q->valid_till)->format('d M Y') : '—' }}</td>
        <td style="padding:9px 12px;color:var(--text);font-weight:500">Rs. {{ number_format($q->total) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['pending'=>'var(--warning-soft)','converted'=>'var(--success-soft)','expired'=>'var(--surface-2)'][$q->status]??'var(--surface-2)' }};color:{{ ['pending'=>'var(--warning)','converted'=>'var(--success)','expired'=>'var(--text-2)'][$q->status]??'var(--text-2)' }}">{{ ucfirst($q->status) }}</span></td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:3px">
                <a href="{{ route('quotations.show',$q) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                <a href="{{ route('quotations.pdf',$q->id) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--info);text-decoration:none"><i class="ti ti-file-invoice" style="font-size:12px"></i></a>
                @if($q->status === 'pending')
                @can('quotations.convert')
                <form method="POST" action="{{ route('quotations.convert',$q->id) }}">
                    @csrf
                    <button type="submit" style="width:26px;height:26px;background:var(--success-soft);border:.5px solid var(--success-border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--success);cursor:pointer" title="Convert to sale"><i class="ti ti-arrow-right" style="font-size:12px"></i></button>
                </form>
                @endcan
                @endif
                @can('quotations.delete')
                <form method="POST" action="{{ route('quotations.destroy',$q) }}" onsubmit="return confirm('Delete quotation {{ $q->quote_no }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Delete" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--danger);cursor:pointer"><i class="ti ti-trash" style="font-size:12px"></i></button>
                </form>
                @endcan
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:32px;text-align:center;color:var(--text-4)">No quotations found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $quotations->links() }}</div>
</div>
@endsection
