{{-- reports/user_reports.blade.php --}}
@extends('layouts.app')
@section('title','User Reports')
@section('page-title','Reports — User / Cashier Reports')
@section('content')
<div style="padding:14px 16px">
<form method="GET" style="display:flex;gap:8px;margin-bottom:14px">
    <input type="date" name="from_date" value="{{ $from }}" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
    <input type="date" name="to_date" value="{{ $to }}" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
    <button type="submit" style="height:34px;padding:0 12px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;cursor:pointer">Apply</button>
</form>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Cashier / User','Sales count','Sales value','Cash collected','Returns','Avg. bill'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($cashiers as $c)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px">
            <div style="display:flex;align-items:center;gap:8px">
                <div style="width:26px;height:26px;background:var(--info-soft);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:var(--info);font-weight:500">{{ strtoupper(substr($c->user?->name??'?',0,2)) }}</div>
                <span style="color:var(--text);font-weight:500">{{ $c->user?->name ?? 'Unknown' }}</span>
            </div>
        </td>
        <td style="padding:9px 12px;color:var(--text)">{{ number_format($c->sale_count) }}</td>
        <td style="padding:9px 12px;color:var(--success);font-weight:500">Rs. {{ number_format($c->total) }}</td>
        <td style="padding:9px 12px;color:var(--text)">Rs. {{ number_format($c->collected) }}</td>
        <td style="padding:9px 12px;color:var(--danger)">—</td>
        <td style="padding:9px 12px;color:var(--text-2)">Rs. {{ $c->sale_count > 0 ? number_format($c->total/$c->sale_count) : 0 }}</td>
    </tr>
    @empty
    <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--text-4)">No data for selected period</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
