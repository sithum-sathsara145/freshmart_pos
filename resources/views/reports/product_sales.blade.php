{{-- reports/product_sales.blade.php --}}
@extends('layouts.app')
@section('title','Product Sales')
@section('page-title','Reports — Product Sales')
@section('content')
<div style="padding:14px 16px">
<form method="GET" style="display:flex;gap:8px;margin-bottom:14px">
    <input type="date" name="from_date" value="{{ $from }}" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
    <input type="date" name="to_date" value="{{ $to }}" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
    <select name="category_id" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
        <option value="">All categories</option>
        @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
    </select>
    <button type="submit" style="height:34px;padding:0 12px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;cursor:pointer">Apply</button>
    <a href="{{ route('reports.export',['product_sales','format'=>'pdf','from_date'=>$from,'to_date'=>$to]) }}" style="height:34px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none;margin-left:auto">
        <i class="ti ti-download" style="font-size:12px"></i>Export
    </a>
</form>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead>
        <tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Product</th>
            <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Category</th>
            <th style="padding:9px 12px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Qty sold</th>
            <th style="padding:9px 12px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Revenue</th>
            <th style="padding:9px 12px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Cost</th>
            <th style="padding:9px 12px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Profit</th>
            <th style="padding:9px 12px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Margin</th>
        </tr>
    </thead>
    <tbody>
    @forelse($products as $p)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $p->product_name }}</td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 8px;border-radius:10px;background:#1e3a5f;color:#60a5fa">{{ $p->category ?? '—' }}</span>
        </td>
        <td style="padding:9px 12px;text-align:right;color:#94a3b8">{{ number_format($p->qty_sold, 2) }}</td>
        <td style="padding:9px 12px;text-align:right;color:#4ade80;font-weight:500">Rs. {{ number_format($p->revenue) }}</td>
        <td style="padding:9px 12px;text-align:right;color:#64748b">Rs. {{ number_format($p->cost) }}</td>
        <td style="padding:9px 12px;text-align:right;color:{{ $p->profit >= 0 ? '#4ade80' : '#f87171' }};font-weight:500">Rs. {{ number_format($p->profit) }}</td>
        <td style="padding:9px 12px;text-align:right;color:#a5b4fc">{{ $p->revenue > 0 ? round($p->profit/$p->revenue*100,1) : 0 }}%</td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:32px;text-align:center;color:#4a5568">No sales data for selected period</td></tr>
    @endforelse
    </tbody>
    @if($products->count())
    <tfoot>
        <tr style="border-top:.5px solid #2a2d3a;background:#0f1117">
            <td colspan="2" style="padding:9px 12px;color:#94a3b8;font-weight:500">Totals</td>
            <td style="padding:9px 12px;text-align:right;color:#94a3b8;font-weight:500">{{ number_format($products->sum('qty_sold'),2) }}</td>
            <td style="padding:9px 12px;text-align:right;color:#4ade80;font-weight:500">Rs. {{ number_format($products->sum('revenue')) }}</td>
            <td style="padding:9px 12px;text-align:right;color:#64748b;font-weight:500">Rs. {{ number_format($products->sum('cost')) }}</td>
            <td style="padding:9px 12px;text-align:right;color:#4ade80;font-weight:500">Rs. {{ number_format($products->sum('profit')) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>
</div>
</div>
@endsection
