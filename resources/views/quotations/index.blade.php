{{-- quotations/index.blade.php --}}
@extends('layouts.app')
@section('title','Quotations')
@section('page-title','Quotations / Estimates')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Total quotes</div><div style="font-size:18px;font-weight:500;color:#e2e8f0">{{ $stats['total'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Pending</div><div style="font-size:18px;font-weight:500;color:#fb923c">{{ $stats['pending'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Converted</div><div style="font-size:18px;font-weight:500;color:#4ade80">{{ $stats['converted'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Conversion rate</div><div style="font-size:18px;font-weight:500;color:#a5b4fc">{{ $stats['total'] > 0 ? round($stats['converted']/$stats['total']*100) : 0 }}%</div></div>
</div>
<div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <a href="{{ route('quotations.create') }}" style="height:34px;padding:0 14px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>New Quotation
    </a>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Quote #','Customer','Date','Valid till','Total','Status','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($quotations as $q)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#60a5fa;font-weight:500">{{ $q->quote_no }}</td>
        <td style="padding:9px 12px;color:#e2e8f0">{{ $q->customer?->name ?? 'Walk-in' }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $q->created_at->format('d M Y') }}</td>
        <td style="padding:9px 12px;color:{{ $q->valid_till && \Carbon\Carbon::parse($q->valid_till)->isPast() ? '#f87171':'#64748b' }}">{{ $q->valid_till ? \Carbon\Carbon::parse($q->valid_till)->format('d M Y') : '—' }}</td>
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">Rs. {{ number_format($q->total) }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['pending'=>'#451a03','converted'=>'#14532d','expired'=>'#1e2130'][$q->status]??'#1e2130' }};color:{{ ['pending'=>'#fb923c','converted'=>'#4ade80','expired'=>'#94a3b8'][$q->status]??'#94a3b8' }}">{{ ucfirst($q->status) }}</span></td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:3px">
                <a href="{{ route('quotations.show',$q) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                <a href="{{ route('quotations.pdf',$q->id) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#60a5fa;text-decoration:none"><i class="ti ti-file-invoice" style="font-size:12px"></i></a>
                @if($q->status === 'pending')
                <form method="POST" action="{{ route('quotations.convert',$q->id) }}">
                    @csrf
                    <button type="submit" style="width:26px;height:26px;background:#14532d;border:.5px solid #166534;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#4ade80;cursor:pointer" title="Convert to sale"><i class="ti ti-arrow-right" style="font-size:12px"></i></button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:32px;text-align:center;color:#4a5568">No quotations found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $quotations->links() }}</div>
</div>
@endsection
