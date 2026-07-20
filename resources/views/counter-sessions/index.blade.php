{{-- counter-sessions/index.blade.php --}}
@extends('layouts.app')
@section('title','Counter Sessions')
@section('page-title','Counter Sessions')
@section('content')
<div style="padding:14px 16px">

{{-- Cash counted out of a drawer that nobody has taken to a cash book yet --}}
@if($totals['awaiting_count'] > 0)
<a href="{{ route('counter-sessions.index', ['status' => 'awaiting']) }}"
   style="display:flex;align-items:center;gap:10px;background:var(--warning-soft);border:.5px solid var(--warning-border);border-radius:8px;padding:11px 14px;margin-bottom:12px;text-decoration:none">
    <i class="ti ti-cash-off" style="font-size:18px;color:var(--warning)"></i>
    <div style="flex:1">
        <div style="font-size:12px;font-weight:600;color:var(--warning)">
            Rs. {{ number_format($totals['awaiting_amount'], 2) }} counted out but not handed in
        </div>
        <div style="font-size:11px;color:var(--text-3)">
            {{ $totals['awaiting_count'] }} closed {{ Str::plural('session', $totals['awaiting_count']) }} still holding cash — record it once it reaches the safe.
        </div>
    </div>
    <i class="ti ti-chevron-right" style="font-size:15px;color:var(--text-3)"></i>
</a>
@endif

{{-- Summary --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:12px 14px">
        <div style="font-size:11px;color:var(--text-3)">Open now</div>
        <div style="font-size:20px;font-weight:600;color:var(--success)">{{ $totals['open'] }}</div>
    </div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:12px 14px">
        <div style="font-size:11px;color:var(--text-3)">Closed sessions</div>
        <div style="font-size:20px;font-weight:600;color:var(--text)">{{ $totals['closed'] }}</div>
    </div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:12px 14px">
        <div style="font-size:11px;color:var(--text-3)">Net variance</div>
        <div style="font-size:20px;font-weight:600;color:{{ $totals['variance'] < 0 ? 'var(--danger)' : ($totals['variance'] > 0 ? 'var(--warning-2)' : 'var(--success)') }}">Rs. {{ number_format($totals['variance'], 2) }}</div>
    </div>
</div>

{{-- Filter --}}
<div style="display:flex;gap:6px;margin-bottom:12px">
    @foreach(['' => 'All', 'open' => 'Open', 'closed' => 'Closed'] as $val => $label)
    <a href="{{ route('counter-sessions.index', $val ? ['status' => $val] : []) }}"
       style="padding:5px 14px;border-radius:20px;font-size:12px;text-decoration:none;border:.5px solid var(--border);{{ request('status') == $val ? 'background:var(--primary-soft);color:var(--primary-text);border-color:var(--primary-border)' : 'background:var(--surface);color:var(--text-2)' }}">{{ $label }}</a>
    @endforeach
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Opened','Counter','Cashier','Opening','Cash sales','Expected','Counted','Variance','Status',''] as $h)
        <th style="padding:9px 12px;text-align:{{ in_array($h,['Opening','Cash sales','Expected','Counted','Variance']) ? 'right' : 'left' }};color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($sessions as $s)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text-2)">{{ optional($s->opened_at)->format('d M Y · h:i A') ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text);font-weight:500">{{ $s->counter?->name ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text-2)">{{ $s->openedBy?->name ?? '—' }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text)">Rs. {{ number_format($s->opening_balance) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-2)">{{ $s->cash_sales !== null ? 'Rs. '.number_format($s->cash_sales) : '—' }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-2)">{{ $s->expected_closing !== null ? 'Rs. '.number_format($s->expected_closing) : '—' }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text)">{{ $s->closing_balance !== null ? 'Rs. '.number_format($s->closing_balance) : '—' }}</td>
        <td style="padding:9px 12px;text-align:right;font-weight:500;color:{{ $s->variance === null ? 'var(--text-4)' : ($s->variance < 0 ? 'var(--danger)' : ($s->variance > 0 ? 'var(--warning-2)' : 'var(--success)')) }}">
            {{ $s->variance === null ? '—' : ($s->variance == 0 ? 'Balanced' : ($s->variance > 0 ? '+' : '−').' Rs. '.number_format(abs($s->variance))) }}
        </td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 8px;border-radius:10px;background:{{ $s->status === 'open' ? 'var(--success-soft)' : 'var(--surface-2)' }};color:{{ $s->status === 'open' ? 'var(--success)' : 'var(--text-2)' }}">{{ ucfirst($s->status) }}</span>
        </td>
        <td style="padding:9px 12px">
            <a href="{{ route('counter-sessions.show', $s) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
        </td>
    </tr>
    @empty
    <tr><td colspan="10" style="padding:32px;text-align:center;color:var(--text-4)">No counter sessions yet</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $sessions->links() }}</div>
</div>
@endsection
