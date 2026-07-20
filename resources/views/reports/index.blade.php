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
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:8px">All reports</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px">
        @foreach($cards as $c)
            @php $soon = empty($c['url']); @endphp
            <a @if(!$soon) href="{{ $c['url'] }}" @endif
               style="display:flex;gap:11px;align-items:flex-start;background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:13px 14px;text-decoration:none;{{ $soon ? 'opacity:.55;cursor:default' : 'cursor:pointer' }}"
               @if(!$soon) onmouseover="this.style.borderColor='var(--primary-border)'" onmouseout="this.style.borderColor='var(--border)'" @endif>
                <div style="width:34px;height:34px;flex-shrink:0;border-radius:8px;background:{{ $c['color'] }}22;display:flex;align-items:center;justify-content:center">
                    <i class="ti {{ $c['icon'] }}" style="font-size:17px;color:{{ $c['color'] }}"></i>
                </div>
                <div style="min-width:0">
                    <div style="font-size:13px;font-weight:500;color:var(--text);display:flex;align-items:center;gap:6px">
                        {{ $c['title'] }}
                        @if($soon)<span style="font-size:9px;padding:1px 6px;border-radius:8px;background:var(--surface-2);color:var(--text-3);font-weight:400">soon</span>@endif
                    </div>
                    <div style="font-size:11px;color:var(--text-3);margin-top:2px;line-height:1.3">{{ $c['desc'] }}</div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
