{{-- reports/partials/header.blade.php — title + date-range picker + compare toggle.
     Expects: $range (App\Support\ReportRange), $title. Optional: $icon, $export (type string). --}}
@once
@push('scripts')
<script src="{{ asset('js/reportcharts.js') }}"></script>
@endpush
@endonce
@php $inp = 'height:32px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none'; @endphp
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:14px">
    <div>
        <div style="font-size:16px;font-weight:600;color:#e2e8f0;display:flex;align-items:center;gap:7px">
            @isset($icon)<i class="ti {{ $icon }}" style="font-size:16px;color:#818cf8"></i>@endisset{{ $title }}
        </div>
        <div style="font-size:11px;color:#64748b;margin-top:3px">
            {{ \Carbon\Carbon::parse($range->fromDate())->format('d M Y') }} — {{ \Carbon\Carbon::parse($range->toDate())->format('d M Y') }}
            @if($range->compare)<span style="color:#818cf8"> · vs {{ \Carbon\Carbon::parse($range->prevFromDate())->format('d M') }}–{{ \Carbon\Carbon::parse($range->prevToDate())->format('d M') }}</span>@endif
        </div>
    </div>
    <form method="GET" action="{{ url()->current() }}" x-data="{ range: '{{ $range->preset }}' }" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
        @foreach(request()->except(['range','from_date','to_date','compare','page']) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <select name="range" x-model="range" @change="range!=='custom' && $el.form.submit()" style="{{ $inp }}">
            @foreach($range->presets() as $v => $l)<option value="{{ $v }}" {{ $range->preset===$v?'selected':'' }}>{{ $l }}</option>@endforeach
        </select>
        <template x-if="range==='custom'">
            <span style="display:flex;gap:6px;align-items:center">
                <input type="date" name="from_date" value="{{ $range->fromDate() }}" style="{{ $inp }}">
                <input type="date" name="to_date" value="{{ $range->toDate() }}" style="{{ $inp }}">
            </span>
        </template>
        <label style="display:flex;align-items:center;gap:5px;height:32px;padding:0 10px;background:#161821;border:.5px solid {{ $range->compare?'#534AB7':'#2a2d3a' }};border-radius:6px;font-size:11px;color:{{ $range->compare?'#a5b4fc':'#94a3b8' }};cursor:pointer">
            <input type="checkbox" name="compare" value="1" {{ $range->compare?'checked':'' }} @change="$el.form.submit()" style="accent-color:#818cf8"> Compare
        </label>
        <button type="submit" x-show="range==='custom'" x-cloak style="height:32px;padding:0 12px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;cursor:pointer">Apply</button>
        @isset($export)
        <a href="{{ route('reports.export', array_merge(['type'=>$export,'format'=>'pdf'], $range->query())) }}" style="height:32px;padding:0 10px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none" title="Export PDF"><i class="ti ti-file-type-pdf" style="font-size:13px"></i></a>
        <a href="{{ route('reports.export', array_merge(['type'=>$export,'format'=>'excel'], $range->query())) }}" style="height:32px;padding:0 10px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none" title="Export Excel"><i class="ti ti-file-spreadsheet" style="font-size:13px"></i></a>
        @endisset
    </form>
</div>
