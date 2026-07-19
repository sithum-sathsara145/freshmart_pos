{{-- hrm/attendance/index.blade.php --}}
@extends('layouts.app')
@section('title','Attendance')
@section('page-title','Attendance')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Present</div><div style="font-size:18px;font-weight:500;color:#4ade80">{{ $stats['present'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Absent</div><div style="font-size:18px;font-weight:500;color:#f87171">{{ $stats['absent'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">On leave</div><div style="font-size:18px;font-weight:500;color:#fb923c">{{ $stats['leave'] }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px;display:flex;align-items:center;gap:8px">
        <form method="GET" style="display:flex;gap:6px;align-items:center">
            <input type="date" name="date" value="{{ $date }}" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:11px;padding:5px 8px;outline:none">
            <button type="submit" style="height:28px;padding:0 8px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:5px;font-size:11px;cursor:pointer">Go</button>
        </form>
    </div>
</div>

@php($canManage = auth()->user()->can('hrm.attendance.manage'))

@if($canManage)
<form method="POST" action="{{ route('hrm.attendance.bulk') }}" x-data="attendanceSheet()">
@csrf
<input type="hidden" name="date" value="{{ $date }}">
@endif

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Staff','Job title','Time in','Time out','Hours','OT','Status',''] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    {{-- Every active staff member gets a row, whether or not attendance exists for
         the day. Previously the marking form only appeared when NOTHING had been
         recorded, so marking one person hid the form for everyone else. --}}
    @forelse($staff as $s)
    @php($a = $rows[$s->id] ?? null)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $s->name }}</td>
        <td style="padding:9px 12px;color:#94a3b8">{{ $s->role }}</td>
        @if($canManage)
        <td style="padding:6px 12px"><input type="time" name="rows[{{ $s->id }}][time_in]" value="{{ $a?->time_in ? substr($a->time_in,0,5) : '' }}" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:4px 6px;outline:none"></td>
        <td style="padding:6px 12px"><input type="time" name="rows[{{ $s->id }}][time_out]" value="{{ $a?->time_out ? substr($a->time_out,0,5) : '' }}" style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:4px 6px;outline:none"></td>
        @else
        <td style="padding:9px 12px;color:#64748b">{{ $a?->time_in ? substr($a->time_in,0,5) : '—' }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $a?->time_out ? substr($a->time_out,0,5) : '—' }}</td>
        @endif
        <td style="padding:9px 12px;color:#e2e8f0">{{ $a && $a->worked_hours > 0 ? number_format($a->worked_hours,1).'h' : '—' }}</td>
        <td style="padding:9px 12px;color:#a5b4fc">{{ $a && $a->overtime_hours > 0 ? number_format($a->overtime_hours,1).'h' : '—' }}</td>
        <td style="padding:6px 12px">
            @if($canManage)
            <select name="rows[{{ $s->id }}][status]" style="height:26px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:0 6px;outline:none">
                <option value="">— not recorded —</option>
                @foreach(['present'=>'Present','absent'=>'Absent','leave'=>'Leave','half_day'=>'Half day'] as $v => $l)
                <option value="{{ $v }}" @selected($a?->status === $v)>{{ $l }}</option>
                @endforeach
            </select>
            @else
            @if($a)
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['present'=>'#14532d','absent'=>'#7f1d1d','leave'=>'#451a03','half_day'=>'#1e3a5f'][$a->status]??'#1e2130' }};color:{{ ['present'=>'#4ade80','absent'=>'#fca5a5','leave'=>'#fb923c','half_day'=>'#60a5fa'][$a->status]??'#94a3b8' }}">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span>
            @else
            <span style="color:#4a5568">Not recorded</span>
            @endif
            @endif
        </td>
        <td style="padding:9px 12px">
            @if($a)
            <a href="{{ route('hrm.attendance.edit',$a) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:inline-flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a>
            @endif
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:20px;text-align:center;color:#4a5568">No active staff in this branch.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

@if($canManage && $staff->count())
<div style="margin-top:12px;display:flex;gap:8px;align-items:center">
    <button type="submit" style="height:34px;padding:0 16px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:5px"><i class="ti ti-device-floppy" style="font-size:14px"></i> Save sheet</button>
    <button type="button" @click="markAllPresent()" style="height:34px;padding:0 14px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;cursor:pointer">Mark all present</button>
    <span style="font-size:11px;color:#64748b">Rows left as “not recorded” are skipped.</span>
</div>
@endif

@if($canManage)
</form>
@endif
</div>

@if($canManage)
@push('scripts')
<script>
function attendanceSheet() {
    return {
        markAllPresent() {
            this.$root.querySelectorAll('select[name$="[status]"]').forEach(s => {
                if (!s.value) s.value = 'present';
            });
        },
    };
}
</script>
@endpush
@endif
@endsection
