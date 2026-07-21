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

    {{-- What happened to the cash after counting --}}
    @if($s->float_retained !== null)
    <div style="margin-top:10px;padding-top:8px;border-top:.5px solid var(--border)">
        <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-2);margin-bottom:6px">
            <span>Kept by the cashier</span>
            <span style="color:var(--text)">Rs. {{ number_format($s->float_retained, 2) }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-2)">
            <span>
                @if($s->deposited_at)
                    Handed in{{ $s->depositAccount ? ' to ' . $s->depositAccount->name : '' }}
                @elseif($s->deposit_amount > 0)
                    Set aside to hand in
                @else
                    Nothing to hand in
                @endif
            </span>
            <span style="color:{{ $s->deposited_at ? 'var(--success)' : ($s->deposit_amount > 0 ? 'var(--warning)' : 'var(--text-3)') }}">Rs. {{ number_format($s->deposit_amount ?? 0, 2) }}</span>
        </div>
    </div>
    @endif
</div>

{{-- Cash set aside at close, still to reach a cash book --}}
@if($s->awaitingHandIn())
<div style="background:var(--warning-soft);border:.5px solid var(--warning-border);border-radius:8px;padding:14px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:600;color:var(--warning);margin-bottom:4px;display:flex;align-items:center;gap:6px">
        <i class="ti ti-cash-off" style="font-size:15px"></i>
        Rs. {{ number_format((float) $s->deposit_amount, 2) }} still to be handed in
    </div>
    <div style="font-size:11px;color:var(--text-3);margin-bottom:12px">
        Counted out of the drawer when the counter closed. Record it here once it has actually reached the cash book —
        that is when the money lands in the books.
    </div>
    @can('accounts.handin')
    <form method="POST" action="{{ route('counter-sessions.hand-in', $s) }}" style="display:flex;gap:8px;flex-wrap:wrap">
        @csrf
        <select name="account_id" required
                style="flex:1;min-width:180px;height:34px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:0 9px;outline:none">
            @foreach($cashBooks as $b)
            <option value="{{ $b->id }}">{{ $b->name }}{{ $b->is_cashier_book ? ' — hand-in book' : '' }}</option>
            @endforeach
        </select>
        <button type="submit" onclick="return confirm('Record Rs. {{ number_format((float) $s->deposit_amount, 2) }} as handed in?')"
                style="height:34px;padding:0 16px;background:var(--success-solid);border:none;border-radius:6px;color:#fff;font-size:12px;font-weight:600;cursor:pointer">
            Record hand-in
        </button>
    </form>
    @else
    <div style="font-size:11px;color:var(--text-3)">You don't have permission to record this.</div>
    @endcan
</div>
@elseif($s->deposited_at)
<div style="background:var(--success-soft);border:.5px solid var(--success-border);border-radius:8px;padding:12px 14px;margin-bottom:12px;font-size:12px;color:var(--success)">
    <i class="ti ti-check" style="font-size:14px;vertical-align:-2px"></i>
    Rs. {{ number_format((float) $s->deposit_amount, 2) }} handed in to
    <b>{{ $s->depositAccount?->name ?? 'a cash book' }}</b> on {{ $s->deposited_at->format('d M Y, h:i A') }}.
</div>
@endif

{{-- Denomination breakdowns --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px">
    @foreach([
        ['Opening count', $s->opening_denoms],
        ['Closing count', $s->closing_denoms],
        // The two halves of the closing count, each as its own record: the notes
        // that physically went, and the notes left behind. The next opening count
        // is checked against what was left, so it has to survive on the record —
        // whoever looks this shift up later can always see what the drawer held.
        ['Sent to the cash book', $s->deposit_denoms],
        ['Left in the drawer', $s->retained_denoms],
    ] as [$label, $denoms])
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
