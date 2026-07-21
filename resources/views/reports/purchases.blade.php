{{-- reports/purchases.blade.php — goods received, returned, and what's still owed --}}
@extends('layouts.app')
@section('title','Purchases')
@section('page-title','Reports — Purchases (GRN)')
@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 2);
    $trim  = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
    $inp   = 'height:32px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none';
    $modes = [
        'details' => 'Goods received', 'summary' => 'By date',
        'supplier' => 'By supplier', 'item' => 'By item', 'returns' => 'Returns (Dr. notes)',
    ];
    $statusColour = ['paid' => 'var(--success)', 'partial' => 'var(--warning-2)', 'unpaid' => 'var(--danger)'];
@endphp
<div style="padding:14px 16px">

@include('reports.partials.header', [
    'title'  => 'Purchases',
    'icon'   => 'ti-truck-delivery',
    'export' => 'purchases_' . $mode,
])

{{-- Mode switch and filters are separate forms on purpose: both carry a "mode",
     and in one form the button's value and the hidden field would collide.
     The menu lists these as separate reports; they are the same deliveries
     totalled differently, so they share one screen and one set of filters. --}}
<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
    <form method="GET">
        @foreach(request()->except(['mode','page']) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <div style="display:inline-flex;flex-wrap:wrap;background:var(--surface);border:.5px solid var(--border);border-radius:7px;padding:2px">
            @foreach($modes as $key => $label)
            <button type="submit" name="mode" value="{{ $key }}"
                    style="height:26px;padding:0 11px;border:none;border-radius:5px;font-size:11.5px;cursor:pointer;
                           background:{{ $mode === $key ? 'var(--primary-soft)' : 'transparent' }};
                           color:{{ $mode === $key ? 'var(--primary-text)' : 'var(--text-3)' }}">{{ $label }}</button>
            @endforeach
        </div>
    </form>

    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        @foreach(request()->except(['mode','supplier_id','payment_status','page']) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <input type="hidden" name="mode" value="{{ $mode }}">
        <select name="supplier_id" onchange="this.form.submit()" style="{{ $inp }}">
            <option value="">All suppliers</option>
            @foreach($suppliers as $s)
            <option value="{{ $s->id }}" {{ (string) $supplierId === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        @if($mode !== 'returns')
        <select name="payment_status" onchange="this.form.submit()" style="{{ $inp }}">
            <option value="">Paid &amp; unpaid</option>
            @foreach(['paid' => 'Settled', 'partial' => 'Part paid', 'unpaid' => 'Not paid'] as $k => $l)
            <option value="{{ $k }}" {{ $status === $k ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        @endif
    </form>
</div>

{{-- Per item the figures are line values, which is not the same as the bill
     totals once a whole-invoice discount is applied — so this mode gets cards
     that match its own table rather than ones that quietly disagree with it. --}}
@php
    $cards = match ($mode) {
        'returns' => [
            ['Debit notes', number_format($totals['count']), 'var(--text)'],
            ['Sent back', 'Rs. '.number_format($totals['total']), 'var(--danger)'],
        ],
        'item' => [
            ['Deliveries', number_format($totals['count']), 'var(--text)'],
            ['Items', number_format($rows->count()), 'var(--text-2)'],
            ['Line value', 'Rs. '.number_format($rows->sum('total')), 'var(--text)'],
            ['Billed after discount', 'Rs. '.number_format($totals['total']), 'var(--text-2)'],
        ],
        default => [
            ['Deliveries', number_format($totals['count']), 'var(--text)'],
            ['Goods received', 'Rs. '.number_format($totals['total']), 'var(--text)'],
            ['Paid', 'Rs. '.number_format($totals['paid']), 'var(--success)'],
            ['Still owed', 'Rs. '.number_format($totals['due']), $totals['due'] > 0 ? 'var(--danger)' : 'var(--text-3)'],
        ],
    };
@endphp
<div style="display:grid;grid-template-columns:repeat({{ count($cards) }},1fr);gap:8px;margin-bottom:14px">
    @foreach($cards as [$l,$v,$c])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:17px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:12px">

@if($mode === 'details')
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Date','Bill no','Supplier','Received by','Due','Total','Paid','Balance','Status'] as $i => $h)
        <th style="padding:9px 10px;text-align:{{ $i < 5 ? 'left' : 'right' }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $p)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 10px;color:var(--text-3);white-space:nowrap">{{ \Carbon\Carbon::parse($p->purchase_date)->format('d M Y') }}</td>
        <td style="padding:8px 10px">
            <a href="{{ route('purchases.show', $p->id) }}" style="color:var(--primary-text);text-decoration:none;font-weight:500">{{ $p->bill_no }}</a>
        </td>
        <td style="padding:8px 10px;color:var(--text)">{{ $p->supplier?->name ?? 'Unknown supplier' }}</td>
        <td style="padding:8px 10px;color:var(--text-3)">{{ $p->user?->name ?? '—' }}</td>
        <td style="padding:8px 10px;color:{{ $p->balance_due > 0 && $p->due_date && \Carbon\Carbon::parse($p->due_date)->isPast() ? 'var(--danger)' : 'var(--text-3)' }};white-space:nowrap">
            {{ $p->due_date ? \Carbon\Carbon::parse($p->due_date)->format('d M Y') : '—' }}
        </td>
        <td style="padding:8px 10px;text-align:right;color:var(--text);font-weight:500">{{ $money($p->total) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text-2)">{{ $money($p->paid_amount) }}</td>
        <td style="padding:8px 10px;text-align:right;color:{{ $p->balance_due > 0 ? 'var(--danger)' : 'var(--text-4)' }}">{{ $money($p->balance_due) }}</td>
        <td style="padding:8px 10px;text-align:right">
            <span style="font-size:10px;padding:2px 8px;border-radius:10px;background:var(--surface-2);color:{{ $statusColour[$p->payment_status] ?? 'var(--text-3)' }}">{{ ucfirst($p->payment_status) }}</span>
        </td>
    </tr>
    @empty
    <tr><td colspan="9" style="padding:28px;text-align:center;color:var(--text-4)">No goods received in this period.</td></tr>
    @endforelse
    </tbody>
    @if($rows->count())
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td colspan="5" style="padding:9px 10px;color:var(--text-2);font-weight:500">Total ({{ $totals['count'] }})</td>
        <td style="padding:9px 10px;text-align:right;color:var(--text);font-weight:600">{{ $money($totals['total']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--success);font-weight:500">{{ $money($totals['paid']) }}</td>
        <td style="padding:9px 10px;text-align:right;color:var(--danger);font-weight:600">{{ $money($totals['due']) }}</td>
        <td></td>
    </tr></tfoot>
    @endif

@elseif($mode === 'returns')
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Date','Dr. note no','Against bill','Supplier','Reason','Credited as','Raised by','Amount'] as $i => $h)
        <th style="padding:9px 10px;text-align:{{ $i < 7 ? 'left' : 'right' }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $r)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 10px;color:var(--text-3);white-space:nowrap">{{ $r->created_at->format('d M Y') }}</td>
        <td style="padding:8px 10px;color:var(--text);font-weight:500">{{ $r->dr_note_no }}</td>
        <td style="padding:8px 10px">
            @if($r->purchase)
            <a href="{{ route('purchases.show', $r->purchase_id) }}" style="color:var(--primary-text);text-decoration:none">{{ $r->purchase->bill_no }}</a>
            @else — @endif
        </td>
        <td style="padding:8px 10px;color:var(--text-2)">{{ $r->supplier?->name ?? $r->purchase?->supplier?->name ?? '—' }}</td>
        <td style="padding:8px 10px;color:var(--text-3)">{{ $r->reason ?: '—' }}</td>
        <td style="padding:8px 10px;color:var(--text-3)">{{ $r->credit_method ? ucfirst(str_replace('_', ' ', $r->credit_method)) : '—' }}</td>
        <td style="padding:8px 10px;color:var(--text-3)">{{ $r->createdBy?->name ?? '—' }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--danger);font-weight:500">{{ $money($r->return_amount) }}</td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:28px;text-align:center;color:var(--text-4)">Nothing sent back in this period.</td></tr>
    @endforelse
    </tbody>
    @if($rows->count())
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td colspan="7" style="padding:9px 10px;color:var(--text-2);font-weight:500">Total ({{ $totals['count'] }})</td>
        <td style="padding:9px 10px;text-align:right;color:var(--danger);font-weight:600">{{ $money($totals['total']) }}</td>
    </tr></tfoot>
    @endif

@else
    @php $head = ['summary' => 'Date', 'supplier' => 'Supplier', 'item' => 'Item'][$mode]; @endphp
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(array_merge([$head, 'Deliveries'], $mode === 'item' ? ['Qty received','Value'] : ['Total','Paid','Still owed']) as $i => $h)
        <th style="padding:9px 12px;text-align:{{ $i === 0 ? 'left' : 'right' }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $r)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--text);font-weight:500">
            {{ $mode === 'summary' ? \Carbon\Carbon::parse($r['label'])->format('D, d M Y') : $r['label'] }}
        </td>
        <td style="padding:8px 12px;text-align:right;color:var(--text-3)">{{ $r['count'] }}</td>
        @if($mode === 'item')
        <td style="padding:8px 12px;text-align:right;color:var(--text-2)">{{ $trim($r['qty']) }} {{ $r['unit'] }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--text);font-weight:500">{{ $money($r['total']) }}</td>
        @else
        <td style="padding:8px 12px;text-align:right;color:var(--text);font-weight:500">{{ $money($r['total']) }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--text-2)">{{ $money($r['paid']) }}</td>
        <td style="padding:8px 12px;text-align:right;color:{{ $r['due'] > 0 ? 'var(--danger)' : 'var(--text-4)' }}">{{ $money($r['due']) }}</td>
        @endif
    </tr>
    @empty
    <tr><td colspan="{{ $mode === 'item' ? 4 : 5 }}" style="padding:28px;text-align:center;color:var(--text-4)">No goods received in this period.</td></tr>
    @endforelse
    </tbody>
    @if($rows->count())
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td style="padding:9px 12px;color:var(--text-2);font-weight:500">Total ({{ $rows->count() }})</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500">{{ $totals['count'] }}</td>
        @if($mode === 'item')
        <td></td>
        <td style="padding:9px 12px;text-align:right;color:var(--text);font-weight:600">{{ $money($rows->sum('total')) }}</td>
        @else
        <td style="padding:9px 12px;text-align:right;color:var(--text);font-weight:600">{{ $money($totals['total']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--success);font-weight:500">{{ $money($totals['paid']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--danger);font-weight:600">{{ $money($totals['due']) }}</td>
        @endif
    </tr></tfoot>
    @endif
@endif

</table>
</div>

<div style="font-size:10.5px;color:var(--text-4);margin-top:10px;line-height:1.5">
    A purchase is recorded when the delivery arrives, so every row here is a goods-received note —
    there is no separate order document waiting to be matched off.
    @if($mode === 'item')
    Line values are what was charged per item, so they add up to more than the billed
    total wherever a discount was taken off the invoice as a whole.
    @elseif($mode === 'returns')
    Debit notes are dated by when they were raised, not by the bill they correct.
    @endif
</div>
</div>
@endsection
