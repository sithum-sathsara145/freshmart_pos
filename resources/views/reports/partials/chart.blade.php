{{-- reports/partials/chart.blade.php — a chart card.
     Expects: $config (array matching reportcharts.js). Optional: $title, $id, $height. --}}
@php $id = $id ?? 'rc-'.\Illuminate\Support\Str::random(6); @endphp
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    @isset($title)<div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">{{ $title }}</div>@endisset
    <div data-chart id="{{ $id }}" style="min-height:{{ $height ?? 120 }}px">
        <script type="application/json">@json($config)</script>
    </div>
</div>
