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
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Staff','Role','Time in','Time out','Hours','OT','Status','Action'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($attendance as $a)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $a->staff?->name }}</td>
        <td style="padding:9px 12px;color:#94a3b8">{{ $a->staff?->role }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $a->time_in ?? '—' }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ $a->time_out ?? '—' }}</td>
        <td style="padding:9px 12px;color:#e2e8f0">{{ $a->worked_hours ? number_format($a->worked_hours,1).'h' : '—' }}</td>
        <td style="padding:9px 12px;color:#a5b4fc">{{ $a->overtime_hours > 0 ? number_format($a->overtime_hours,1).'h' : '—' }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['present'=>'#14532d','absent'=>'#7f1d1d','leave'=>'#451a03','half_day'=>'#1e3a5f'][$a->status]??'#1e2130' }};color:{{ ['present'=>'#4ade80','absent'=>'#fca5a5','leave'=>'#fb923c','half_day'=>'#60a5fa'][$a->status]??'#94a3b8' }}">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span></td>
        <td style="padding:9px 12px"><a href="{{ route('hrm.attendance.edit',$a) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a></td>
    </tr>
    @empty
    @foreach($staff as $s)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#e2e8f0;font-weight:500">{{ $s->name }}</td>
        <td style="padding:9px 12px;color:#94a3b8">{{ $s->role }}</td>
        <td colspan="5" style="padding:9px 12px;color:#4a5568">Not recorded</td>
        <td style="padding:9px 12px">
            <form method="POST" action="{{ route('hrm.attendance.store') }}" style="display:flex;gap:4px">
                @csrf
                <input type="hidden" name="staff_id" value="{{ $s->id }}">
                <input type="hidden" name="date" value="{{ $date }}">
                <select name="status" style="height:26px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:0 6px;outline:none">
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="leave">Leave</option>
                </select>
                <button type="submit" style="height:26px;padding:0 8px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:5px;font-size:11px;cursor:pointer">Mark</button>
            </form>
        </td>
    </tr>
    @endforeach
    @endforelse
    </tbody>
</table>
</div>
</div>
@endsection
