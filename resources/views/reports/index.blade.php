{{-- reports/index.blade.php — reports overview hub --}}
@extends('layouts.app')
@section('title','Reports')
@section('page-title','Reports')
@section('content')
<div style="padding:14px 16px">

    @include('reports.partials.header', ['range' => $range, 'title' => 'Overview', 'icon' => 'ti-chart-histogram'])

    {{-- KPI row --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;margin-bottom:12px">
        @foreach($kpis as $k)
            @include('reports.partials.kpi', array_merge($k, ['range' => $range]))
        @endforeach
    </div>

    {{-- Net-sales trend --}}
    <div style="margin-bottom:16px">
        @include('reports.partials.chart', ['title' => 'Net sales', 'config' => $trend, 'height' => 240])
    </div>

    {{-- Report cards --}}
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:8px">All reports</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px">
        @foreach($cards as $c)
            @php $soon = empty($c['url']); @endphp
            <a @if(!$soon) href="{{ $c['url'] }}" @endif
               style="display:flex;gap:11px;align-items:flex-start;background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:13px 14px;text-decoration:none;{{ $soon ? 'opacity:.55;cursor:default' : 'cursor:pointer' }}"
               @if(!$soon) onmouseover="this.style.borderColor='#534AB7'" onmouseout="this.style.borderColor='#2a2d3a'" @endif>
                <div style="width:34px;height:34px;flex-shrink:0;border-radius:8px;background:{{ $c['color'] }}22;display:flex;align-items:center;justify-content:center">
                    <i class="ti {{ $c['icon'] }}" style="font-size:17px;color:{{ $c['color'] }}"></i>
                </div>
                <div style="min-width:0">
                    <div style="font-size:13px;font-weight:500;color:#e2e8f0;display:flex;align-items:center;gap:6px">
                        {{ $c['title'] }}
                        @if($soon)<span style="font-size:9px;padding:1px 6px;border-radius:8px;background:#1e2130;color:#64748b;font-weight:400">soon</span>@endif
                    </div>
                    <div style="font-size:11px;color:#64748b;margin-top:2px;line-height:1.3">{{ $c['desc'] }}</div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
