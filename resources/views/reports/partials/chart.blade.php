{{-- reports/partials/chart.blade.php — a chart card.
     Expects: $config (array matching reportcharts.js). Optional: $title, $id, $height. --}}
@php $id = $id ?? 'rc-'.\Illuminate\Support\Str::random(6); @endphp
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    @isset($title)<div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">{{ $title }}</div>@endisset
    <div data-chart id="{{ $id }}" style="min-height:{{ $height ?? 120 }}px">
        <script type="application/json">@json($config)</script>
    </div>
</div>
