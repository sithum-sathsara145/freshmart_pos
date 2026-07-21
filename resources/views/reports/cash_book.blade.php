{{-- reports/cash_book.blade.php — ledger, account balances, and till hand-overs --}}
@extends('layouts.app')
@section('title','Cash Book')
@section('page-title','Reports — Cash Book')
@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 2);
    $sel   = 'height:32px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none';
    $views = ['ledger' => 'Cash book', 'balances' => 'Account balances', 'handover' => 'Till hand-overs'];
@endphp
<div style="padding:14px 16px">

@include('reports.partials.header', [
    'title'  => 'Cash book',
    'icon'   => 'ti-book-2',
    'export' => 'cash_book_' . $view,
])

<form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px">
    @foreach(request()->except(['view','account_id','type','page']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach

    <div style="display:flex;background:var(--surface);border:.5px solid var(--border);border-radius:7px;padding:2px">
        @foreach($views as $key => $label)
        <button type="submit" name="view" value="{{ $key }}"
                style="height:26px;padding:0 11px;border:none;border-radius:5px;font-size:11.5px;cursor:pointer;
                       background:{{ $view === $key ? 'var(--primary-soft)' : 'transparent' }};
                       color:{{ $view === $key ? 'var(--primary-text)' : 'var(--text-3)' }}">{{ $label }}</button>
        @endforeach
    </div>

    @if($view !== 'handover')
    <select name="type" onchange="this.form.submit()" style="{{ $sel }}">
        <option value="">Cash &amp; bank</option>
        <option value="cash" @selected($type === 'cash')>Cash only</option>
        <option value="bank" @selected($type === 'bank')>Bank only</option>
    </select>
    <select name="account_id" onchange="this.form.submit()" style="{{ $sel }}">
        <option value="">All accounts</option>
        @foreach($accounts as $a)
        <option value="{{ $a->id }}" @selected($accountId == $a->id)>{{ $a->name }}</option>
        @endforeach
    </select>
    @endif
    <input type="hidden" name="view" value="{{ $view }}">
</form>

@if($view === 'handover')
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
        @foreach([
            ['Counted at close','Rs. '.number_format($totals['counted']),'var(--text)'],
            ['Sent to a book','Rs. '.number_format($totals['sent']),'var(--success)'],
            ['Left as float','Rs. '.number_format($totals['kept']),'var(--text-2)'],
            ['Variance','Rs. '.number_format($totals['variance']), $totals['variance'] < 0 ? 'var(--danger)' : ($totals['variance'] > 0 ? 'var(--warning-2)' : 'var(--success)')],
        ] as [$l,$v,$c])
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
            <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
            <div style="font-size:16px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
        </div>
        @endforeach
    </div>

    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            @foreach(['Closed','Counter','Closed by','Expected','Counted','Variance','Sent','Notes sent','Into','Left as float'] as $i => $h)
            <th style="padding:9px 10px;text-align:{{ in_array($h,['Expected','Counted','Variance','Sent','Left as float']) ? 'right' : 'left' }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @forelse($sessions as $s)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:8px 10px;color:var(--text-3);white-space:nowrap">{{ $s->closed_at?->format('d M · h:i A') }}</td>
            <td style="padding:8px 10px;color:var(--text);font-weight:500">{{ $s->counter?->name ?? '—' }}</td>
            <td style="padding:8px 10px;color:var(--text-2)">{{ $s->closedBy?->name ?? '—' }}</td>
            <td style="padding:8px 10px;text-align:right;color:var(--text-3)">{{ $money($s->expected_closing) }}</td>
            <td style="padding:8px 10px;text-align:right;color:var(--text)">{{ $money($s->closing_balance) }}</td>
            <td style="padding:8px 10px;text-align:right;color:{{ $s->variance < 0 ? 'var(--danger)' : ($s->variance > 0 ? 'var(--warning-2)' : 'var(--success)') }}">{{ $money($s->variance) }}</td>
            <td style="padding:8px 10px;text-align:right;color:var(--success);font-weight:500">{{ $money($s->deposit_amount) }}</td>
            <td style="padding:8px 10px;color:var(--text-3);font-size:10.5px">
                {{ $s->deposit_denoms ? collect($s->deposit_denoms)->map(fn($q,$d) => $q.' × '.number_format((int) $d))->implode(' · ') : '—' }}
            </td>
            <td style="padding:8px 10px;color:var(--text-2)">{{ $s->depositAccount?->name ?? '—' }}</td>
            <td style="padding:8px 10px;text-align:right;color:var(--text-2)">{{ $money($s->float_retained) }}</td>
        </tr>
        @empty
        <tr><td colspan="10" style="padding:28px;text-align:center;color:var(--text-4)">No tills closed in this period.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>

@elseif($view === 'balances')
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
        @foreach([
            ['Opening','Rs. '.number_format($totals['opening']),'var(--text-2)'],
            ['Money in','Rs. '.number_format($totals['money_in']),'var(--success)'],
            ['Money out','Rs. '.number_format($totals['money_out']),'var(--danger)'],
            ['Closing','Rs. '.number_format($totals['closing']),'var(--text)'],
        ] as [$l,$v,$c])
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
            <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
            <div style="font-size:16px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
        </div>
        @endforeach
    </div>

    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            @foreach(['Account','Type','Opening','Money in','Money out','Closing'] as $i => $h)
            <th style="padding:9px 12px;text-align:{{ $i < 2 ? 'left' : 'right' }};color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @forelse($balances as $b)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:8px 12px;color:var(--text);font-weight:500">
                <a href="{{ route('accounts.transactions', $b['account']->id) }}" style="color:var(--primary-text);text-decoration:none">{{ $b['account']->name }}</a>
            </td>
            <td style="padding:8px 12px;color:var(--text-3)">{{ ucfirst($b['account']->type) }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--text-2)">{{ $money($b['opening']) }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--success)">{{ $money($b['money_in']) }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--danger)">{{ $money($b['money_out']) }}</td>
            <td style="padding:8px 12px;text-align:right;color:var(--text);font-weight:500">{{ $money($b['closing']) }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="padding:28px;text-align:center;color:var(--text-4)">No accounts.</td></tr>
        @endforelse
        </tbody>
        @if($balances->count())
        <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
            <td colspan="2" style="padding:9px 12px;color:var(--text-2);font-weight:500">Total</td>
            <td style="padding:9px 12px;text-align:right;color:var(--text-2);font-weight:500">{{ $money($totals['opening']) }}</td>
            <td style="padding:9px 12px;text-align:right;color:var(--success);font-weight:500">{{ $money($totals['money_in']) }}</td>
            <td style="padding:9px 12px;text-align:right;color:var(--danger);font-weight:500">{{ $money($totals['money_out']) }}</td>
            <td style="padding:9px 12px;text-align:right;color:var(--text);font-weight:600">{{ $money($totals['closing']) }}</td>
        </tr></tfoot>
        @endif
    </table>
    </div>

@else
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
        @foreach([
            ['Brought forward','Rs. '.number_format($totals['opening']),'var(--text-2)'],
            ['Money in','Rs. '.number_format($totals['money_in']),'var(--success)'],
            ['Money out','Rs. '.number_format($totals['money_out']),'var(--danger)'],
            ['Carried forward','Rs. '.number_format($totals['closing']),'var(--text)'],
        ] as [$l,$v,$c])
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
            <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
            <div style="font-size:16px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
        </div>
        @endforeach
    </div>

    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            @foreach(['Date','Account','Description','Reference','In','Out','Balance'] as $h)
            <th style="padding:9px 10px;text-align:{{ in_array($h,['In','Out','Balance']) ? 'right' : 'left' }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        <tr style="border-bottom:.5px solid var(--surface-3);background:var(--bg)">
            <td colspan="6" style="padding:8px 10px;color:var(--text-3);font-style:italic">Brought forward</td>
            <td style="padding:8px 10px;text-align:right;color:var(--text-2);font-weight:500">{{ $money($opening) }}</td>
        </tr>
        @php($running = $opening)
        @forelse($entries as $e)
        @php($running += $e->direction === 'credit' ? (float) $e->amount : -(float) $e->amount)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:8px 10px;color:var(--text-3);white-space:nowrap">{{ $e->occurred_at?->format('d M · h:i A') }}</td>
            <td style="padding:8px 10px;color:var(--text-2)">{{ $e->account?->name ?? '—' }}</td>
            <td style="padding:8px 10px;color:var(--text)">
                {{ $e->label() }}
                @if($e->counterparty)<span style="color:var(--text-3)"> · {{ $e->counterparty->name }}</span>@endif
            </td>
            <td style="padding:8px 10px;color:var(--text-3);font-size:11px">{{ $e->reference ?: '—' }}</td>
            <td style="padding:8px 10px;text-align:right;color:var(--success)">{{ $e->direction === 'credit' ? $money($e->amount) : '' }}</td>
            <td style="padding:8px 10px;text-align:right;color:var(--danger)">{{ $e->direction === 'credit' ? '' : $money($e->amount) }}</td>
            <td style="padding:8px 10px;text-align:right;color:var(--text-2);white-space:nowrap">{{ $money($running) }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="padding:28px;text-align:center;color:var(--text-4)">Nothing moved in this period.</td></tr>
        @endforelse
        </tbody>
        @if($entries->count())
        <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
            <td colspan="4" style="padding:9px 10px;color:var(--text-2);font-weight:500">Carried forward</td>
            <td style="padding:9px 10px;text-align:right;color:var(--success);font-weight:500">{{ $money($totals['money_in']) }}</td>
            <td style="padding:9px 10px;text-align:right;color:var(--danger);font-weight:500">{{ $money($totals['money_out']) }}</td>
            <td style="padding:9px 10px;text-align:right;color:var(--text);font-weight:600">{{ $money($totals['closing']) }}</td>
        </tr></tfoot>
        @endif
    </table>
    </div>
@endif

</div>
@endsection
