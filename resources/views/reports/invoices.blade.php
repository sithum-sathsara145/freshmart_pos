{{-- reports/invoices.blade.php — invoice details, per-day summary, credit notes --}}
@extends('layouts.app')
@section('title','Invoice Report')
@section('page-title','Reports — Invoices')
@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 2);
    $sel   = 'height:32px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none';
    $modes = ['details' => 'Invoice details', 'summary' => 'Daily summary', 'cancelled' => 'Credit notes'];
@endphp
<div style="padding:14px 16px">

@include('reports.partials.header', [
    'title'  => 'Invoices',
    'icon'   => 'ti-file-invoice',
    'export' => 'invoices_' . $mode,
])

{{-- Mode + filters. Everything is carried in the querystring so the range picker
     above and the export buttons keep whatever is selected here. --}}
<form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px">
    @foreach(request()->except(['mode','user_id','counter_id','payment_method','status','page']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach

    <div style="display:flex;background:var(--surface);border:.5px solid var(--border);border-radius:7px;padding:2px">
        @foreach($modes as $key => $label)
        <button type="submit" name="mode" value="{{ $key }}"
                style="height:26px;padding:0 11px;border:none;border-radius:5px;font-size:11.5px;cursor:pointer;
                       background:{{ $mode === $key ? 'var(--primary-soft)' : 'transparent' }};
                       color:{{ $mode === $key ? 'var(--primary-text)' : 'var(--text-3)' }}">{{ $label }}</button>
        @endforeach
    </div>

    <select name="user_id" onchange="this.form.submit()" style="{{ $sel }}">
        <option value="">All cashiers</option>
        @foreach($cashiers as $c)
        <option value="{{ $c->id }}" @selected($filters['user_id'] == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>

    <select name="counter_id" onchange="this.form.submit()" style="{{ $sel }}">
        <option value="">All counters</option>
        @foreach($counters as $c)
        <option value="{{ $c->id }}" @selected($filters['counter_id'] == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>

    @if($mode !== 'cancelled')
    <select name="payment_method" onchange="this.form.submit()" style="{{ $sel }}">
        <option value="">Any payment</option>
        @foreach(['cash'=>'Cash','card'=>'Card','credit'=>'Credit','bank_transfer'=>'Bank transfer','mixed'=>'Mixed'] as $v => $l)
        <option value="{{ $v }}" @selected($filters['payment_method'] === $v)>{{ $l }}</option>
        @endforeach
    </select>
    <select name="status" onchange="this.form.submit()" style="{{ $sel }}">
        <option value="">Any status</option>
        @foreach(['paid'=>'Paid','partial'=>'Partial','pending'=>'Pending','returned'=>'Returned'] as $v => $l)
        <option value="{{ $v }}" @selected($filters['status'] === $v)>{{ $l }}</option>
        @endforeach
    </select>
    @endif

    <input type="hidden" name="mode" value="{{ $mode }}">
</form>

{{-- Totals --}}
@if($mode === 'cancelled')
<div style="display:grid;grid-template-columns:repeat(2,minmax(0,200px));gap:8px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Credit notes</div>
        <div style="font-size:18px;font-weight:500;color:var(--text)">{{ $totals['count'] }}</div>
    </div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Refunded</div>
        <div style="font-size:18px;font-weight:500;color:var(--danger)">Rs. {{ number_format($totals['net']) }}</div>
    </div>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-bottom:14px">
    @foreach([
        ['Invoices', number_format($totals['count']), 'var(--text)'],
        ['Gross', 'Rs. '.number_format($totals['gross']), 'var(--text-2)'],
        ['Discount', 'Rs. '.number_format($totals['discount']), 'var(--warning-2)'],
        ['Tax', 'Rs. '.number_format($totals['tax']), 'var(--text-2)'],
        ['Net sales', 'Rs. '.number_format($totals['net']), 'var(--success)'],
        ['Outstanding', 'Rs. '.number_format($totals['due']), 'var(--danger)'],
    ] as [$l,$v,$c])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:16px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>
@endif

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:12px">

@if($mode === 'details')
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Date','Invoice','Customer','Cashier','Counter','Payment','Gross','Discount','Tax','Net','Paid','Due','Status'] as $i => $h)
        <th style="padding:9px 10px;text-align:{{ $i >= 6 && $h !== 'Status' ? 'right' : 'left' }};color:var(--text-3);font-weight:500;font-size:11px;white-space:nowrap">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $s)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 10px;color:var(--text-3);white-space:nowrap">{{ $s->created_at->format('d M · h:i A') }}</td>
        <td style="padding:8px 10px;color:var(--text);font-weight:500">
            <a href="{{ route('sales.show', $s->id) }}" style="color:var(--primary-text);text-decoration:none">{{ $s->invoice_no }}</a>
        </td>
        <td style="padding:8px 10px;color:var(--text-2)">{{ $s->customer?->name ?? 'Walk-in' }}</td>
        <td style="padding:8px 10px;color:var(--text-2)">{{ $s->user?->name ?? '—' }}</td>
        <td style="padding:8px 10px;color:var(--text-3)">{{ $s->counter?->name ?? '—' }}</td>
        <td style="padding:8px 10px;color:var(--text-3)">{{ ucfirst(str_replace('_',' ', $s->payment_method)) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text-2)">{{ $money($s->subtotal) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--warning-2)">{{ $money((float) $s->discount_amount + (float) $s->coupon_discount) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text-3)">{{ $money($s->tax_amount) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--text);font-weight:500">{{ $money($s->total) }}</td>
        <td style="padding:8px 10px;text-align:right;color:var(--success)">{{ $money($s->paid_amount) }}</td>
        <td style="padding:8px 10px;text-align:right;color:{{ (float) $s->total - (float) $s->paid_amount > 0.004 ? 'var(--danger)' : 'var(--text-4)' }}">{{ $money(max(0, (float) $s->total - (float) $s->paid_amount)) }}</td>
        <td style="padding:8px 10px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['paid'=>'var(--success-soft)','partial'=>'var(--warning-soft)','pending'=>'var(--warning-soft)','returned'=>'var(--danger-soft)'][$s->status] ?? 'var(--surface-2)' }};color:{{ ['paid'=>'var(--success)','partial'=>'var(--warning)','pending'=>'var(--warning)','returned'=>'var(--danger)'][$s->status] ?? 'var(--text-2)' }}">{{ ucfirst($s->status) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="13" style="padding:28px;text-align:center;color:var(--text-4)">No invoices in this period.</td></tr>
    @endforelse
    </tbody>

@elseif($mode === 'summary')
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Date','Invoices','Gross','Discount','Tax','Net sales','Collected'] as $i => $h)
        <th style="padding:9px 12px;text-align:{{ $i === 0 ? 'left' : 'right' }};color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $d)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--text)">{{ \Carbon\Carbon::parse($d['date'])->format('D, d M Y') }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--text-2)">{{ $d['count'] }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--text-2)">{{ $money($d['gross']) }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--warning-2)">{{ $money($d['discount']) }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--text-3)">{{ $money($d['tax']) }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--text);font-weight:500">{{ $money($d['net']) }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--success)">{{ $money($d['paid']) }}</td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:28px;text-align:center;color:var(--text-4)">No invoices in this period.</td></tr>
    @endforelse
    </tbody>
    @if($rows->count())
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td style="padding:9px 12px;color:var(--text-2);font-weight:500">Total</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-2);font-weight:500">{{ $totals['count'] }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-2);font-weight:500">{{ $money($totals['gross']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--warning-2);font-weight:500">{{ $money($totals['discount']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500">{{ $money($totals['tax']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text);font-weight:600">{{ $money($totals['net']) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--success);font-weight:500">{{ $money($totals['paid']) }}</td>
    </tr></tfoot>
    @endif

@else
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Date','Credit note','Against invoice','Customer','Reason','Refund','Refunded by'] as $i => $h)
        <th style="padding:9px 12px;text-align:{{ $h === 'Refund' ? 'right' : 'left' }};color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($rows as $r)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:8px 12px;color:var(--text-3);white-space:nowrap">{{ $r->created_at->format('d M · h:i A') }}</td>
        <td style="padding:8px 12px;color:var(--text);font-weight:500">{{ $r->credit_note_no }}</td>
        <td style="padding:8px 12px;color:var(--primary-text)">{{ $r->sale?->invoice_no ?? '—' }}</td>
        <td style="padding:8px 12px;color:var(--text-2)">{{ $r->sale?->customer?->name ?? 'Walk-in' }}</td>
        <td style="padding:8px 12px;color:var(--text-3)">{{ Str::limit($r->reason, 40) }}</td>
        <td style="padding:8px 12px;text-align:right;color:var(--danger);font-weight:500">{{ $money($r->return_amount) }}</td>
        <td style="padding:8px 12px;color:var(--text-3)">{{ $r->createdBy?->name ?? '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:28px;text-align:center;color:var(--text-4)">No credit notes in this period.</td></tr>
    @endforelse
    </tbody>
@endif

</table>
</div>

@if($mode === 'cancelled')
<div style="font-size:10.5px;color:var(--text-4);margin-top:10px;line-height:1.5">
    A voided sale is reversed and removed outright, so this lists credit notes —
    the durable record of money going back to a customer.
</div>
@endif
</div>
@endsection
