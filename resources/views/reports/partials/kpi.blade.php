{{-- reports/partials/kpi.blade.php — one KPI scorecard.
     Expects: $label, $value. Optional: $delta (%|null), $invert (bool), $sub (string), $color. --}}
@php
    $delta  = $delta  ?? null;
    $invert = $invert ?? false;   // true when "up" is bad (returns, expenses, variance…)
    $color  = $color  ?? '#e2e8f0';
    $up     = ! is_null($delta) && $delta >= 0;
    $good   = $invert ? ! $up : $up;
@endphp
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px">
    <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px">{{ $label }}</div>
    <div style="font-size:20px;font-weight:600;color:{{ $color }};line-height:1.15">{{ $value }}</div>
    @if(! is_null($delta))
    <div style="font-size:11px;margin-top:4px;color:{{ $good ? '#4ade80' : '#f87171' }}">
        <i class="ti {{ $up ? 'ti-trending-up' : 'ti-trending-down' }}" style="font-size:12px"></i>
        {{ ($delta >= 0 ? '+' : '') . number_format($delta, 1) }}% <span style="color:#64748b">vs prev</span>
    </div>
    @elseif(!empty($sub))
    <div style="font-size:11px;margin-top:4px;color:#64748b">{{ $sub }}</div>
    @elseif($range?->compare ?? false)
    <div style="font-size:11px;margin-top:4px;color:#4a5568">— no prior data</div>
    @endif
</div>
