{{-- hrm/dashboard.blade.php --}}
@extends('layouts.app')
@section('title','HRM Dashboard')
@section('page-title','HRM — Human Resource Management')
@section('content')
<div style="padding:14px 16px">

{{-- Self check-in. The check-in/check-out endpoints existed from the start but
     nothing in the app ever called them. Only shown when this login actually has
     an HR record to record against. --}}
@if($myStaff)
<div x-data="clockCard()" style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:12px 14px;margin-bottom:14px;display:flex;align-items:center;gap:12px">
    <div style="width:34px;height:34px;background:#1e3a5f;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#60a5fa;flex-shrink:0">
        <i class="ti ti-clock-hour-8" style="font-size:17px"></i>
    </div>
    <div style="flex:1">
        <div style="font-size:12px;color:#e2e8f0;font-weight:500">{{ $myStaff->name }}</div>
        <div style="font-size:11px;color:#64748b" x-text="statusText">
            @if($myAttendance?->time_in)
                In at {{ substr($myAttendance->time_in, 0, 5) }}{{ $myAttendance->time_out ? ' · out at '.substr($myAttendance->time_out, 0, 5).' · '.number_format($myAttendance->worked_hours, 1).'h' : '' }}
            @else
                Not checked in today
            @endif
        </div>
    </div>
    <div style="display:flex;gap:6px">
        <button type="button" @click="clock('in')" :disabled="busy"
            style="height:32px;padding:0 14px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;cursor:pointer">Check in</button>
        <button type="button" @click="clock('out')" :disabled="busy"
            style="height:32px;padding:0 14px;background:#1e2130;color:#94a3b8;border:.5px solid #2a2d3a;border-radius:6px;font-size:12px;cursor:pointer">Check out</button>
    </div>
    <div x-show="error" x-cloak x-text="error" style="font-size:11px;color:#fca5a5"></div>
</div>
@endif

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    @foreach([['Total staff',$stats['total'],'#e2e8f0','ti-users'],['On duty',$stats['on_duty'],'#4ade80','ti-user-check'],['On leave',$stats['on_leave'],'#fb923c','ti-beach'],['Payroll due','Rs. '.number_format($stats['payroll_due']),'#a5b4fc','ti-cash']] as [$l,$v,$c,$i])
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:#64748b;margin-bottom:3px;display:flex;align-items:center;gap:4px"><i class="ti {{ $i }}" style="font-size:12px"></i>{{ $l }}</div>
        <div style="font-size:18px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Today's attendance</div>
    @forelse($todayAttendance as $att)
    <div style="display:flex;align-items:center;gap:9px;padding:6px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <div style="width:28px;height:28px;background:#1e3a5f;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:500;color:#60a5fa;flex-shrink:0">
            {{ strtoupper(substr($att->staff->name, 0, 2)) }}
        </div>
        <div style="flex:1">
            <div style="color:#e2e8f0;font-weight:500">{{ $att->staff->name }}</div>
            <div style="color:#64748b">{{ $att->staff->role }} {{ $att->time_in ? '· In: '.$att->time_in : '' }}</div>
        </div>
        <span style="font-size:10px;padding:2px 8px;border-radius:10px;font-weight:500;background:{{ ['present'=>'#14532d','absent'=>'#7f1d1d','leave'=>'#451a03'][$att->status]??'#1e2130' }};color:{{ ['present'=>'#4ade80','absent'=>'#fca5a5','leave'=>'#fb923c'][$att->status]??'#94a3b8' }}">{{ ucfirst($att->status) }}</span>
    </div>
    @empty
    <div style="text-align:center;color:#4a5568;font-size:12px;padding:16px">No attendance recorded today</div>
    @endforelse
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Department overview</div>
    @foreach($byDepartment as $dept)
    <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px">
            <span style="color:#94a3b8">{{ $dept->role }}</span>
            <span style="color:#e2e8f0">{{ $dept->total }} staff</span>
        </div>
        <div style="height:5px;background:#1e2130;border-radius:3px;overflow:hidden">
            <div style="height:100%;background:#818cf8;border-radius:3px;width:{{ min(100, ($dept->total/max(1,$stats['total']))*100) }}%"></div>
        </div>
    </div>
    @endforeach
    <div style="margin-top:14px;display:flex;gap:6px;flex-wrap:wrap">
        <a href="{{ route('hrm.staff.index') }}" style="font-size:11px;padding:5px 10px;background:#312e81;color:#a5b4fc;border-radius:6px;text-decoration:none">Staff members</a>
        <a href="{{ route('hrm.attendance.index') }}" style="font-size:11px;padding:5px 10px;background:#1e2130;color:#94a3b8;border-radius:6px;text-decoration:none">Attendance</a>
        <a href="{{ route('hrm.leaves.index') }}" style="font-size:11px;padding:5px 10px;background:#1e2130;color:#94a3b8;border-radius:6px;text-decoration:none">Leaves</a>
        @can('hrm.holidays.manage')
        <a href="{{ route('hrm.holidays.index') }}" style="font-size:11px;padding:5px 10px;background:#1e2130;color:#94a3b8;border-radius:6px;text-decoration:none">Holidays</a>
        @endcan
        {{-- Managers don't hold hrm.payroll.manage, so an ungated link was a guaranteed 403. --}}
        @can('hrm.payroll.manage')
        <a href="{{ route('hrm.payroll.index') }}" style="font-size:11px;padding:5px 10px;background:#1e2130;color:#94a3b8;border-radius:6px;text-decoration:none">Payroll</a>
        @endcan
    </div>
</div>
</div>
</div>

@if($myStaff)
@push('scripts')
<script>
function clockCard() {
    return {
        busy: false,
        error: '',
        statusText: null,          // null = keep the server-rendered text
        async clock(direction) {
            this.busy = true;
            this.error = '';
            try {
                const res = await fetch(direction === 'in' ? @js(route('hrm.attendance.check_in')) : @js(route('hrm.attendance.check_out')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!data.success) {
                    this.error = data.message || 'Could not record that.';
                } else {
                    this.statusText = direction === 'in'
                        ? `Checked in at ${data.time}`
                        : `Checked out at ${data.time} · ${Number(data.hours).toFixed(1)}h`;
                }
            } catch (e) {
                this.error = 'Network error — try again.';
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endpush
@endif
@endsection
