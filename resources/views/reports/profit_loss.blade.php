{{-- reports/profit_loss.blade.php --}}
@extends('layouts.app')
@section('title','Profit & Loss')
@section('page-title','Reports — Profit & Loss')
@section('content')
<div style="padding:14px 16px">

{{-- Date filter --}}
<form method="GET" style="display:flex;gap:8px;margin-bottom:14px;align-items:center">
    @foreach(['Today','This week','This month','This year'] as $p)
    <a href="?period={{ strtolower(str_replace(' ','_',$p)) }}" style="padding:5px 12px;border-radius:20px;font-size:12px;font-weight:500;cursor:pointer;border:.5px solid var(--border);color:var(--text-3);background:var(--surface);text-decoration:none;{{ request('period','')==strtolower(str_replace(' ','_',$p)) ? 'background:var(--primary-soft);color:var(--primary-text);border-color:var(--primary-border)' : '' }}">{{ $p }}</a>
    @endforeach
    <input type="date" name="from_date" value="{{ $from }}" style="height:32px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
    <input type="date" name="to_date" value="{{ $to }}" style="height:32px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
    <button type="submit" style="height:32px;padding:0 12px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;cursor:pointer">Apply</button>
    <a href="{{ route('reports.export',['profit_loss','format'=>'pdf','from_date'=>$from,'to_date'=>$to]) }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none;margin-left:auto">
        <i class="ti ti-download" style="font-size:13px"></i>Export PDF
    </a>
</form>

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    @foreach([['Revenue','Rs. '.number_format($salesRevenue),'var(--success)','ti-arrow-up'],['Expenses','Rs. '.number_format($purchaseCost + $totalExpenses),'var(--danger)','ti-arrow-down'],['Gross profit','Rs. '.number_format($grossProfit),'var(--primary-text)','ti-trending-up'],['Net profit','Rs. '.number_format($netProfit),'var(--success)','ti-chart-bar']] as [$l,$v,$c,$i])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px;display:flex;align-items:center;gap:4px"><i class="ti {{ $i }}" style="font-size:12px;color:{{ $c }}"></i>{{ $l }}</div>
        <div style="font-size:18px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>

{{-- Chart + P&L statement --}}
<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:12px;margin-bottom:12px">

{{-- Chart --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Daily revenue</div>
    <div style="display:flex;align-items:flex-end;gap:4px;height:100px">
        @php $maxR = $chartData->max('revenue') ?: 1; @endphp
        @foreach($chartData as $d)
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px">
            <div style="width:100%;background:var(--primary);border-radius:3px 3px 0 0;min-height:3px;height:{{ max(3, round(($d->revenue/$maxR)*85)) }}px" title="Rs. {{ number_format($d->revenue) }}"></div>
            <div style="font-size:8px;color:var(--text-3)">{{ \Carbon\Carbon::parse($d->date)->format('d') }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- P&L statement --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Profit & Loss statement</div>
    @foreach([
        ['Sales revenue', $salesRevenue, 'var(--success)', false],
        ['Purchase cost', $purchaseCost, 'var(--danger)', true],
        ['Gross profit', $grossProfit, 'var(--primary-text)', false],
        ['Expenses', $totalExpenses, 'var(--danger)', true],
        ['Discounts given', $totalDiscounts, 'var(--danger)', true],
    ] as [$l,$v,$c,$neg])
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:.5px solid var(--surface-3);font-size:12px">
        <span style="color:var(--text-3)">{{ $l }}</span>
        <span style="color:{{ $c }};font-weight:500">{{ $neg ? '- ' : '' }}Rs. {{ number_format($v) }}</span>
    </div>
    @endforeach
    <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:14px;font-weight:500;color:{{ $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' }}">
        <span>Net profit</span><span>Rs. {{ number_format($netProfit) }}</span>
    </div>
</div>
</div>

{{-- Top products --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Top selling products</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            <th style="padding:6px 10px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Product</th>
            <th style="padding:6px 10px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Qty</th>
            <th style="padding:6px 10px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Revenue</th>
            <th style="padding:6px 10px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Profit</th>
        </tr></thead>
        <tbody>
        @foreach($topProducts as $p)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:7px 10px;color:var(--text)">{{ $p['name'] }}</td>
            <td style="padding:7px 10px;text-align:right;color:var(--text-2)">{{ number_format($p['qty'],1) }}</td>
            <td style="padding:7px 10px;text-align:right;color:var(--text)">Rs. {{ number_format($p['revenue']) }}</td>
            <td style="padding:7px 10px;text-align:right;color:var(--success);font-weight:500">Rs. {{ number_format($p['profit']) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>
@endsection
