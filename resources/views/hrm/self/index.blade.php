{{-- hrm/self/index.blade.php --}}
@extends('layouts.app')
@section('title','My HR')
@section('page-title','My HR')
@section('content')
<div style="padding:14px 16px;max-width:900px">

@include('hrm.self._tabs', ['active' => 'index'])

{{-- Clock card --}}
<div x-data="myClock()" style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <div style="width:36px;height:36px;background:#1e3a5f;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#60a5fa;flex-shrink:0">
        <i class="ti ti-clock-hour-8" style="font-size:18px"></i>
    </div>
    <div style="flex:1;min-width:170px">
        <div style="font-size:13px;color:#e2e8f0;font-weight:500">{{ $staff->name }}</div>
        <div style="font-size:11px;color:#64748b" x-text="statusText">
            @if($todayRow?->time_in)
                In at {{ substr($todayRow->time_in, 0, 5) }}{{ $todayRow->time_out ? ' · out at '.substr($todayRow->time_out, 0, 5).' · '.number_format($todayRow->worked_hours, 1).'h' : '' }}
            @else
                Not checked in today
            @endif
        </div>
    </div>
    @can('hrm.self.attendance')
    <div style="display:flex;gap:6px">
        <button type="button" @click="clock('in')" :disabled="busy"
            style="height:32px;padding:0 14px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;cursor:pointer">Check in</button>
        <button type="button" @click="clock('out')" :disabled="busy"
            style="height:32px;padding:0 14px;background:#1e2130;color:#94a3b8;border:.5px solid #2a2d3a;border-radius:6px;font-size:12px;cursor:pointer">Check out</button>
    </div>
    @endcan
    <div x-show="error" x-cloak x-text="error" style="font-size:11px;color:#fca5a5;width:100%"></div>
</div>

{{-- This month --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:12px">
    @foreach([
        ['Days present',$summary['present'],'#4ade80'],
        ['On leave',$summary['leave'],'#fb923c'],
        ['Absent',$summary['absent'],'#f87171'],
        ['Hours',number_format($summary['hours'],1),'#e2e8f0'],
        ['Overtime',number_format($summary['ot'],1).'h','#a5b4fc'],
    ] as [$l,$v,$c])
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:#64748b;margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:17px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>
<div style="font-size:10px;color:#4a5568;margin:-6px 0 14px 2px">This month · {{ now()->format('F Y') }}</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">

    {{-- Leave balance --}}
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:9px">
            <div style="font-size:12px;font-weight:500;color:#94a3b8">Leave balance {{ $year }}</div>
            <a href="{{ route('my.leave') }}" style="font-size:11px;color:#a5b4fc;text-decoration:none">Request →</a>
        </div>
        @foreach($balances as $b)
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:5px 0;border-bottom:.5px solid #1a1d2a">
            <span style="color:#64748b">{{ $b['label'] }}</span>
            @if($b['tracked'])
            <span>
                <span style="color:{{ $b['remaining'] <= 0 ? '#f87171' : '#4ade80' }};font-weight:500">{{ rtrim(rtrim(number_format($b['remaining'],1),'0'),'.') }}</span>
                <span style="color:#4a5568"> / {{ rtrim(rtrim(number_format($b['entitled'],1),'0'),'.') }} days</span>
            </span>
            @else
            <span style="color:#64748b;font-size:11px">unpaid</span>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Latest payslip --}}
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:9px">
            <div style="font-size:12px;font-weight:500;color:#94a3b8">Latest payslip</div>
            <a href="{{ route('my.payslips') }}" style="font-size:11px;color:#a5b4fc;text-decoration:none">All payslips →</a>
        </div>
        @if($lastPayslip)
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:5px 0;border-bottom:.5px solid #1a1d2a">
            <span style="color:#64748b">Period</span><span style="color:#e2e8f0">{{ $lastPayslip->periodLabel() }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:5px 0;border-bottom:.5px solid #1a1d2a">
            <span style="color:#64748b">Net pay</span><span style="color:#4ade80;font-weight:500">Rs. {{ number_format($lastPayslip->net_salary, 2) }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:5px 0">
            <span style="color:#64748b">Status</span>
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $lastPayslip->status === 'paid' ? '#14532d' : '#451a03' }};color:{{ $lastPayslip->status === 'paid' ? '#4ade80' : '#fb923c' }}">{{ ucfirst($lastPayslip->status) }}</span>
        </div>
        <a href="{{ route('my.payslip', $lastPayslip->id) }}" style="display:block;text-align:center;margin-top:9px;height:30px;line-height:30px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:11px;text-decoration:none">View payslip</a>
        @else
        <div style="color:#64748b;font-size:12px;padding:6px 0">No payslip issued yet.</div>
        @endif
    </div>

</div>
</div>

@push('scripts')
<script>
function myClock() {
    return {
        busy: false, error: '', statusText: null,
        async clock(direction) {
            this.busy = true; this.error = '';
            try {
                const res = await fetch(direction === 'in' ? @js(route('my.checkin')) : @js(route('my.checkout')), {
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
@endsection
