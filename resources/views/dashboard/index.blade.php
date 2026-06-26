@extends('layouts.app')
@section('title','Dashboard — FreshMart POS')
@section('page-title','Dashboard')

@section('content')
<div style="padding:14px 16px">

{{-- Stats row --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px">
    @php
        $change = $yesterdaySales > 0 ? round((($todaySales->total - $yesterdaySales) / $yesterdaySales) * 100, 1) : 0;
    @endphp
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px">
        <div style="font-size:10px;color:#64748b;margin-bottom:4px;display:flex;align-items:center;gap:4px">
            <i class="ti ti-cash" style="font-size:12px"></i>Today's Sales
        </div>
        <div style="font-size:20px;font-weight:500;color:#e2e8f0">Rs. {{ number_format($todaySales->total ?? 0) }}</div>
        <div style="font-size:10px;margin-top:3px;color:{{ $change >= 0 ? '#4ade80' : '#f87171' }}">
            {{ $change >= 0 ? '↑' : '↓' }} {{ abs($change) }}% vs yesterday
        </div>
    </div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px">
        <div style="font-size:10px;color:#64748b;margin-bottom:4px;display:flex;align-items:center;gap:4px">
            <i class="ti ti-receipt" style="font-size:12px"></i>Transactions today
        </div>
        <div style="font-size:20px;font-weight:500;color:#e2e8f0">{{ $todaySales->count ?? 0 }}</div>
        <div style="font-size:10px;margin-top:3px;color:#64748b">This month: {{ $monthSales->count ?? 0 }}</div>
    </div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px">
        <div style="font-size:10px;color:#64748b;margin-bottom:4px;display:flex;align-items:center;gap:4px">
            <i class="ti ti-alert-triangle" style="font-size:12px"></i>Low stock items
        </div>
        <div style="font-size:20px;font-weight:500;color:{{ $lowStockCount > 0 ? '#fb923c' : '#e2e8f0' }}">{{ $lowStockCount }}</div>
        <div style="font-size:10px;margin-top:3px;color:#f87171">{{ $outOfStockCount }} out of stock</div>
    </div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px">
        <div style="font-size:10px;color:#64748b;margin-bottom:4px;display:flex;align-items:center;gap:4px">
            <i class="ti ti-users" style="font-size:12px"></i>Staff on duty
        </div>
        <div style="font-size:20px;font-weight:500;color:#e2e8f0">{{ $staffOnDuty }} / {{ $totalStaff }}</div>
        @if($pendingOnlineOrders > 0)
        <div style="font-size:10px;margin-top:3px;color:#fb923c">{{ $pendingOnlineOrders }} online orders pending</div>
        @else
        <div style="font-size:10px;margin-top:3px;color:#64748b">All branches</div>
        @endif
    </div>
</div>

{{-- Chart + Low stock --}}
<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:10px;margin-bottom:10px">

    {{-- Chart --}}
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px;display:flex;justify-content:space-between">
            <span>Sales — last 7 days</span>
            <span style="font-size:10px;color:#64748b">Rs. {{ number_format($monthSales->total ?? 0) }} this month</span>
        </div>
        <div style="display:flex;align-items:flex-end;gap:5px;height:110px" id="sales-chart">
            @php $maxVal = $chartData->max('total') ?: 1; @endphp
            @foreach($chartData as $day)
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px">
                <div style="font-size:9px;color:#94a3b8">{{ number_format($day->total/1000,0) }}K</div>
                <div style="width:100%;background:#818cf8;border-radius:3px 3px 0 0;height:{{ max(4, round(($day->total/$maxVal)*90)) }}px"
                     title="Rs. {{ number_format($day->total) }}"></div>
                <div style="font-size:9px;color:#64748b">{{ \Carbon\Carbon::parse($day->date)->format('D') }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Low stock --}}
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px;display:flex;justify-content:space-between">
            <span><i class="ti ti-alert-triangle" style="color:#fb923c;margin-right:4px;font-size:13px"></i>Low stock alert</span>
            <a href="{{ route('reports.stock_alert') }}" style="font-size:10px;color:#818cf8;text-decoration:none">View all →</a>
        </div>
        @forelse($lowStockProducts as $p)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:.5px solid #1a1d2a;font-size:11px">
            <div>
                <div style="color:#e2e8f0;font-weight:500">{{ $p->name }}</div>
                <div style="color:#64748b">{{ $p->category?->name }}</div>
            </div>
            <div style="text-align:right">
                <div style="color:{{ $p->current_stock <= 0 ? '#f87171' : '#fb923c' }};font-weight:500">{{ $p->current_stock }} units</div>
                <div style="color:#64748b">Min: {{ $p->min_stock }}</div>
            </div>
        </div>
        @empty
        <div style="text-align:center;color:#4a5568;font-size:12px;padding:16px">✅ All items well stocked</div>
        @endforelse
    </div>
</div>

{{-- Recent sales + Top products --}}
<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:10px">

    {{-- Recent sales --}}
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px;display:flex;justify-content:space-between">
            <span>Recent sales</span>
            <a href="{{ route('sales.index') }}" style="font-size:10px;color:#818cf8;text-decoration:none">View all →</a>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:11px">
            <thead>
                <tr style="border-bottom:.5px solid #2a2d3a">
                    <th style="text-align:left;padding:5px 8px;color:#64748b;font-weight:500">Invoice</th>
                    <th style="text-align:left;padding:5px 8px;color:#64748b;font-weight:500">Customer</th>
                    <th style="text-align:left;padding:5px 8px;color:#64748b;font-weight:500">Time</th>
                    <th style="text-align:right;padding:5px 8px;color:#64748b;font-weight:500">Total</th>
                    <th style="text-align:center;padding:5px 8px;color:#64748b;font-weight:500">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentSales as $sale)
                <tr style="border-bottom:.5px solid #1a1d2a">
                    <td style="padding:6px 8px;color:#818cf8">{{ $sale->invoice_no }}</td>
                    <td style="padding:6px 8px;color:#e2e8f0">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                    <td style="padding:6px 8px;color:#64748b">{{ $sale->created_at->format('H:i') }}</td>
                    <td style="padding:6px 8px;text-align:right;color:#e2e8f0;font-weight:500">Rs. {{ number_format($sale->total) }}</td>
                    <td style="padding:6px 8px;text-align:center">
                        <span style="font-size:10px;padding:2px 7px;border-radius:10px;font-weight:500;
                            background:{{ $sale->status === 'paid' ? '#14532d' : ($sale->status === 'partial' ? '#451a03' : '#7f1d1d') }};
                            color:{{ $sale->status === 'paid' ? '#4ade80' : ($sale->status === 'partial' ? '#fb923c' : '#fca5a5') }}">
                            {{ ucfirst($sale->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:20px;text-align:center;color:#4a5568">No sales today yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Top products --}}
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">
            <i class="ti ti-trending-up" style="color:#4ade80;margin-right:4px;font-size:13px"></i>Top products today
        </div>
        @forelse($topProducts as $i => $item)
        <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:.5px solid #1a1d2a;font-size:11px">
            <div style="width:18px;height:18px;background:#1e2130;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#64748b;flex-shrink:0">{{ $i+1 }}</div>
            <div style="flex:1">
                <div style="color:#e2e8f0;font-weight:500">{{ $item->product->name }}</div>
                <div style="color:#64748b">{{ number_format($item->qty_sold, 1) }} sold</div>
            </div>
            <div style="color:#4ade80;font-weight:500">Rs. {{ number_format($item->revenue) }}</div>
        </div>
        @empty
        <div style="text-align:center;color:#4a5568;font-size:12px;padding:16px">No sales data yet</div>
        @endforelse
    </div>
</div>

</div>
@endsection
