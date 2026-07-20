{{-- reports/partials/kpi.blade.php — one KPI scorecard.
     Expects: $label, $value. Optional: $delta (%|null), $invert (bool), $sub (string), $color. --}}
@php
    $delta  = $delta  ?? null;
    $invert = $invert ?? false;   // true when "up" is bad (returns, expenses, variance…)
    $color  = $color  ?? 'var(--text)';
    $up     = ! is_null($delta) && $delta >= 0;
    $good   = $invert ? ! $up : $up;
@endphp
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:12px 14px">
    <div style="font-size:10px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px">{{ $label }}</div>
    <div style="font-size:20px;font-weight:600;color:{{ $color }};line-height:1.15">{{ $value }}</div>
    @if(! is_null($delta))
    <div style="font-size:11px;margin-top:4px;color:{{ $good ? 'var(--success)' : 'var(--danger)' }}">
        <i class="ti {{ $up ? 'ti-trending-up' : 'ti-trending-down' }}" style="font-size:12px"></i>
        {{ ($delta >= 0 ? '+' : '') . number_format($delta, 1) }}% <span style="color:var(--text-3)">vs prev</span>
    </div>
    @elseif(!empty($sub))
    <div style="font-size:11px;margin-top:4px;color:var(--text-3)">{{ $sub }}</div>
    @elseif($range?->compare ?? false)
    <div style="font-size:11px;margin-top:4px;color:var(--text-4)">— no prior data</div>
    @endif
</div>
