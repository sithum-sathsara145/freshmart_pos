{{-- reports/expenses.blade.php --}}
@extends('layouts.app')
@section('title','Expenses Report')
@section('page-title','Reports — Expenses')
@section('content')
<div style="padding:14px 16px">
<form method="GET" style="display:flex;gap:8px;margin-bottom:14px">
    <input type="date" name="from_date" value="{{ $from }}" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
    <input type="date" name="to_date" value="{{ $to }}" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
    <select name="category_id" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
        <option value="">All categories</option>
        @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
    </select>
    <button type="submit" style="height:34px;padding:0 12px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;cursor:pointer">Apply</button>
    <a href="{{ route('reports.export',['expenses','format'=>'pdf','from_date'=>$from,'to_date'=>$to]) }}" style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none;margin-left:auto">
        <i class="ti ti-download" style="font-size:12px"></i>Export
    </a>
</form>

{{-- Details / by category / by date. The menu lists these as three reports;
     they are the same expenses totalled differently. --}}
<form method="GET" style="margin-bottom:14px">
    @foreach(request()->except(['mode','page']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    <div style="display:inline-flex;background:var(--surface);border:.5px solid var(--border);border-radius:7px;padding:2px">
        @foreach(['details' => 'Details', 'category' => 'By category', 'date' => 'By date'] as $key => $label)
        <button type="submit" name="mode" value="{{ $key }}"
                style="height:26px;padding:0 11px;border:none;border-radius:5px;font-size:11.5px;cursor:pointer;
                       background:{{ $mode === $key ? 'var(--primary-soft)' : 'transparent' }};
                       color:{{ $mode === $key ? 'var(--primary-text)' : 'var(--text-3)' }}">{{ $label }}</button>
        @endforeach
    </div>
</form>

{{-- Category breakdown. The name comes off the eager-loaded relation: the query
     groups by id, so the model itself has no name of its own. --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;margin-bottom:14px">
    @foreach($byCategory as $cat)
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $cat->category?->name ?? 'Uncategorised' }}</div>
        <div style="font-size:16px;font-weight:500;color:var(--danger)">Rs. {{ number_format($cat->total) }}</div>
        <div style="font-size:10px;color:var(--text-4);margin-top:2px">{{ $cat->entries }} {{ Str::plural('record', $cat->entries) }}</div>
    </div>
    @endforeach
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px;border-color:var(--primary-border)">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Total</div>
        <div style="font-size:16px;font-weight:500;color:var(--danger)">Rs. {{ number_format($total) }}</div>
    </div>
</div>

@if($mode !== 'details')
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $mode === 'category' ? 'Category' : 'Date' }}</th>
        <th style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Entries</th>
        <th style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Amount</th>
        <th style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Share</th>
    </tr></thead>
    <tbody>
    @forelse($mode === 'category' ? $byCategory : $byDate as $r)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text)">
            {{ $mode === 'category' ? ($r->category?->name ?? 'Uncategorised') : \Carbon\Carbon::parse($r->expense_date)->format('D, d M Y') }}
        </td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-3)">{{ $r->entries }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--danger);font-weight:500">Rs. {{ number_format($r->total, 2) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-3)">{{ $total > 0 ? number_format($r->total / $total * 100, 1) : '0.0' }}%</td>
    </tr>
    @empty
    <tr><td colspan="4" style="padding:28px;text-align:center;color:var(--text-4)">No expenses in this period.</td></tr>
    @endforelse
    </tbody>
    <tfoot><tr style="border-top:.5px solid var(--border);background:var(--bg)">
        <td style="padding:9px 12px;color:var(--text-2);font-weight:500">Total</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-2);font-weight:500">{{ ($mode === 'category' ? $byCategory : $byDate)->sum('entries') }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--danger);font-weight:600">Rs. {{ number_format($total, 2) }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--text-3)">100%</td>
    </tr></tfoot>
</table>
</div>
@else

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead>
        <tr style="border-bottom:.5px solid var(--border)">
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Date</th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Category</th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Description</th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Account</th>
            <th style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Amount</th>
        </tr>
    </thead>
    <tbody>
    @forelse($expenses as $e)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text-3)">{{ \Carbon\Carbon::parse($e->expense_date)->format('d M Y') }}</td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 8px;border-radius:10px;background:var(--primary-soft);color:var(--primary-text)">{{ $e->category?->name ?? '—' }}</span>
        </td>
        <td style="padding:9px 12px;color:var(--text)">{{ $e->description }}</td>
        <td style="padding:9px 12px;color:var(--text-2)">{{ $e->account?->name ?? '—' }}</td>
        <td style="padding:9px 12px;text-align:right;color:var(--danger);font-weight:500">Rs. {{ number_format($e->amount) }}</td>
    </tr>
    @empty
    <tr><td colspan="5" style="padding:32px;text-align:center;color:var(--text-4)">No expenses in selected period</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $expenses->links() }}</div>
@endif
</div>
@endsection
