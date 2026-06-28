{{-- counter-sessions/index.blade.php --}}
@extends('layouts.app')
@section('title','Counter Sessions')
@section('page-title','Counter Sessions')
@section('content')
<div style="padding:14px 16px">

{{-- Summary --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px">
        <div style="font-size:11px;color:#64748b">Open now</div>
        <div style="font-size:20px;font-weight:600;color:#4ade80">{{ $totals['open'] }}</div>
    </div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px">
        <div style="font-size:11px;color:#64748b">Closed sessions</div>
        <div style="font-size:20px;font-weight:600;color:#e2e8f0">{{ $totals['closed'] }}</div>
    </div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px">
        <div style="font-size:11px;color:#64748b">Net variance</div>
        <div style="font-size:20px;font-weight:600;color:{{ $totals['variance'] < 0 ? '#f87171' : ($totals['variance'] > 0 ? '#fbbf24' : '#4ade80') }}">Rs. {{ number_format($totals['variance'], 2) }}</div>
    </div>
</div>

{{-- Filter --}}
<div style="display:flex;gap:6px;margin-bottom:12px">
    @foreach(['' => 'All', 'open' => 'Open', 'closed' => 'Closed'] as $val => $label)
    <a href="{{ route('counter-sessions.index', $val ? ['status' => $val] : []) }}"
       style="padding:5px 14px;border-radius:20px;font-size:12px;text-decoration:none;border:.5px solid #2a2d3a;{{ request('status') == $val ? 'background:#312e81;color:#a5b4fc;border-color:#534AB7' : 'background:#161821;color:#94a3b8' }}">{{ $label }}</a>
    @endforeach
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Opened','Counter','Cashier','Opening','Cash sales','Expected','Counted','Variance','Status',''] as $h)
        <th style="padding:9px 12px;text-align:{{ in_array($h,['Opening','Cash sales','Expected','Counted','Variance']) ? 'right' : 'left' }};color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($sessions as $s)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#94a3b8">{{ optional($s->opened_at)->format('d M Y · h:i A') ?? '—' }}</td>
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $s->counter?->name ?? '—' }}</td>
        <td style="padding:9px 12px;color:#94a3b8">{{ $s->openedBy?->name ?? '—' }}</td>
        <td style="padding:9px 12px;text-align:right;color:#e2e8f0">Rs. {{ number_format($s->opening_balance) }}</td>
        <td style="padding:9px 12px;text-align:right;color:#94a3b8">{{ $s->cash_sales !== null ? 'Rs. '.number_format($s->cash_sales) : '—' }}</td>
        <td style="padding:9px 12px;text-align:right;color:#94a3b8">{{ $s->expected_closing !== null ? 'Rs. '.number_format($s->expected_closing) : '—' }}</td>
        <td style="padding:9px 12px;text-align:right;color:#e2e8f0">{{ $s->closing_balance !== null ? 'Rs. '.number_format($s->closing_balance) : '—' }}</td>
        <td style="padding:9px 12px;text-align:right;font-weight:500;color:{{ $s->variance === null ? '#4a5568' : ($s->variance < 0 ? '#f87171' : ($s->variance > 0 ? '#fbbf24' : '#4ade80')) }}">
            {{ $s->variance === null ? '—' : ($s->variance == 0 ? 'Balanced' : ($s->variance > 0 ? '+' : '−').' Rs. '.number_format(abs($s->variance))) }}
        </td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 8px;border-radius:10px;background:{{ $s->status === 'open' ? '#14532d' : '#1e2130' }};color:{{ $s->status === 'open' ? '#4ade80' : '#94a3b8' }}">{{ ucfirst($s->status) }}</span>
        </td>
        <td style="padding:9px 12px">
            <a href="{{ route('counter-sessions.show', $s) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
        </td>
    </tr>
    @empty
    <tr><td colspan="10" style="padding:32px;text-align:center;color:#4a5568">No counter sessions yet</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $sessions->links() }}</div>
</div>
@endsection
