{{-- counter-sessions/show.blade.php --}}
@extends('layouts.app')
@section('title','Counter Session')
@section('page-title','Counter Session')
@section('content')
@php $s = $counterSession; @endphp
<div style="padding:14px 16px;max-width:760px">

<a href="{{ route('counter-sessions.index') }}" style="font-size:12px;color:var(--primary);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px"><i class="ti ti-arrow-left" style="font-size:13px"></i> Back to sessions</a>

{{-- Header --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="display:flex;justify-content:space-between;align-items:start">
        <div>
            <div style="font-size:15px;font-weight:600;color:var(--text)">{{ $s->counter?->name ?? 'Counter' }}</div>
            <div style="font-size:11px;color:var(--text-3);margin-top:2px">Session #{{ $s->id }}</div>
        </div>
        <span style="font-size:10px;padding:3px 10px;border-radius:10px;background:{{ $s->status === 'open' ? 'var(--success-soft)' : 'var(--surface-2)' }};color:{{ $s->status === 'open' ? 'var(--success)' : 'var(--text-2)' }}">{{ ucfirst($s->status) }}</span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px;font-size:12px">
        <div><span style="color:var(--text-3)">Opened by:</span> <span style="color:var(--text)">{{ $s->openedBy?->name ?? '—' }}</span></div>
        <div><span style="color:var(--text-3)">Opened at:</span> <span style="color:var(--text)">{{ optional($s->opened_at)->format('d M Y · h:i A') ?? '—' }}</span></div>
        <div><span style="color:var(--text-3)">Closed by:</span> <span style="color:var(--text)">{{ $s->closedBy?->name ?? '—' }}</span></div>
        <div><span style="color:var(--text-3)">Closed at:</span> <span style="color:var(--text)">{{ optional($s->closed_at)->format('d M Y · h:i A') ?? '—' }}</span></div>
    </div>
</div>

{{-- Reconciliation --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Reconciliation</div>
    <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-2);margin-bottom:6px"><span>Opening float</span><span style="color:var(--text)">Rs. {{ number_format($s->opening_balance, 2) }}</span></div>
    <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-2);margin-bottom:6px"><span>Cash sales</span><span style="color:var(--text)">{{ $s->cash_sales !== null ? 'Rs. '.number_format($s->cash_sales, 2) : '—' }}</span></div>
    <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-2);margin-bottom:6px;padding-bottom:6px;border-bottom:.5px solid var(--border)"><span>Expected in drawer</span><span style="color:var(--text)">{{ $s->expected_closing !== null ? 'Rs. '.number_format($s->expected_closing, 2) : '—' }}</span></div>
    <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-2);margin:6px 0"><span>Counted at close</span><span style="color:var(--text);font-weight:500">{{ $s->closing_balance !== null ? 'Rs. '.number_format($s->closing_balance, 2) : '—' }}</span></div>
    @if($s->variance !== null)
    <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:600;color:{{ $s->variance < 0 ? 'var(--danger)' : ($s->variance > 0 ? 'var(--warning-2)' : 'var(--success)') }}">
        <span>{{ $s->variance == 0 ? 'Balanced' : ($s->variance > 0 ? 'Over by' : 'Short by') }}</span>
        <span>Rs. {{ number_format(abs($s->variance), 2) }}</span>
    </div>
    @endif
</div>

{{-- Denomination breakdowns --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    @foreach([['Opening count', $s->opening_denoms], ['Closing count', $s->closing_denoms]] as [$label, $denoms])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">{{ $label }}</div>
        @if(empty($denoms))
        <div style="font-size:12px;color:var(--text-4)">Not recorded</div>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr style="border-bottom:.5px solid var(--border)">
                <th style="padding:5px 4px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Denom</th>
                <th style="padding:5px 4px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">Qty</th>
                <th style="padding:5px 4px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Value</th>
            </tr></thead>
            <tbody>
            @php $sum = 0; @endphp
            @foreach($denoms as $denom => $qty) @php $val = (int)$denom * (int)$qty; $sum += $val; @endphp
            <tr style="border-bottom:.5px solid var(--surface-3)">
                <td style="padding:5px 4px;color:var(--text)">Rs. {{ number_format((int)$denom) }}</td>
                <td style="padding:5px 4px;text-align:center;color:var(--text-2)">{{ $qty }}</td>
                <td style="padding:5px 4px;text-align:right;color:var(--text)">{{ number_format($val) }}</td>
            </tr>
            @endforeach
            <tr><td colspan="2" style="padding:6px 4px;color:var(--text-2);font-weight:500">Total</td><td style="padding:6px 4px;text-align:right;color:var(--primary-text);font-weight:600">Rs. {{ number_format($sum) }}</td></tr>
            </tbody>
        </table>
        @endif
    </div>
    @endforeach
</div>

</div>
@endsection
