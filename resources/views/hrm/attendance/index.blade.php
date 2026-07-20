{{-- hrm/attendance/index.blade.php --}}
@extends('layouts.app')
@section('title','Attendance')
@section('page-title','Attendance')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Present</div><div style="font-size:18px;font-weight:500;color:var(--success)">{{ $stats['present'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">Absent</div><div style="font-size:18px;font-weight:500;color:var(--danger)">{{ $stats['absent'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:var(--text-3);margin-bottom:3px">On leave</div><div style="font-size:18px;font-weight:500;color:var(--warning)">{{ $stats['leave'] }}</div></div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px;display:flex;align-items:center;gap:8px">
        <form method="GET" style="display:flex;gap:6px;align-items:center">
            <input type="date" name="date" value="{{ $date }}" style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:11px;padding:5px 8px;outline:none">
            <button type="submit" style="height:28px;padding:0 8px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:5px;font-size:11px;cursor:pointer">Go</button>
        </form>
    </div>
</div>

@php($canManage = auth()->user()->can('hrm.attendance.manage'))

@if($canManage)
<form method="POST" action="{{ route('hrm.attendance.bulk') }}" x-data="attendanceSheet()">
@csrf
<input type="hidden" name="date" value="{{ $date }}">
@endif

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Staff','Job title','Time in','Time out','Hours','OT','Status',''] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    {{-- Every active staff member gets a row, whether or not attendance exists for
         the day. Previously the marking form only appeared when NOTHING had been
         recorded, so marking one person hid the form for everyone else. --}}
    @forelse($staff as $s)
    @php($a = $rows[$s->id] ?? null)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text);font-weight:500">{{ $s->name }}</td>
        <td style="padding:9px 12px;color:var(--text-2)">{{ $s->role }}</td>
        @if($canManage)
        <td style="padding:6px 12px"><input type="time" name="rows[{{ $s->id }}][time_in]" value="{{ $a?->time_in ? substr($a->time_in,0,5) : '' }}" style="background:var(--bg);border:.5px solid var(--border);border-radius:5px;color:var(--text);font-size:11px;padding:4px 6px;outline:none"></td>
        <td style="padding:6px 12px"><input type="time" name="rows[{{ $s->id }}][time_out]" value="{{ $a?->time_out ? substr($a->time_out,0,5) : '' }}" style="background:var(--bg);border:.5px solid var(--border);border-radius:5px;color:var(--text);font-size:11px;padding:4px 6px;outline:none"></td>
        @else
        <td style="padding:9px 12px;color:var(--text-3)">{{ $a?->time_in ? substr($a->time_in,0,5) : '—' }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $a?->time_out ? substr($a->time_out,0,5) : '—' }}</td>
        @endif
        <td style="padding:9px 12px;color:var(--text)">{{ $a && $a->worked_hours > 0 ? number_format($a->worked_hours,1).'h' : '—' }}</td>
        <td style="padding:9px 12px;color:var(--primary-text)">{{ $a && $a->overtime_hours > 0 ? number_format($a->overtime_hours,1).'h' : '—' }}</td>
        <td style="padding:6px 12px">
            @if($canManage)
            <select name="rows[{{ $s->id }}][status]" style="height:26px;background:var(--bg);border:.5px solid var(--border);border-radius:5px;color:var(--text);font-size:11px;padding:0 6px;outline:none">
                <option value="">— not recorded —</option>
                @foreach(['present'=>'Present','absent'=>'Absent','leave'=>'Leave','half_day'=>'Half day'] as $v => $l)
                <option value="{{ $v }}" @selected($a?->status === $v)>{{ $l }}</option>
                @endforeach
            </select>
            @else
            @if($a)
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['present'=>'var(--success-soft)','absent'=>'var(--danger-soft)','leave'=>'var(--warning-soft)','half_day'=>'var(--info-soft)'][$a->status]??'var(--surface-2)' }};color:{{ ['present'=>'var(--success)','absent'=>'var(--danger-text)','leave'=>'var(--warning)','half_day'=>'var(--info)'][$a->status]??'var(--text-2)' }}">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span>
            @else
            <span style="color:var(--text-4)">Not recorded</span>
            @endif
            @endif
        </td>
        <td style="padding:9px 12px">
            @if($a)
            <a href="{{ route('hrm.attendance.edit',$a) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:inline-flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a>
            @endif
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="padding:20px;text-align:center;color:var(--text-4)">No active staff in this branch.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

@if($canManage && $staff->count())
<div style="margin-top:12px;display:flex;gap:8px;align-items:center">
    <button type="submit" style="height:34px;padding:0 16px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:5px"><i class="ti ti-device-floppy" style="font-size:14px"></i> Save sheet</button>
    <button type="button" @click="markAllPresent()" style="height:34px;padding:0 14px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;cursor:pointer">Mark all present</button>
    <span style="font-size:11px;color:var(--text-3)">Rows left as “not recorded” are skipped.</span>
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
