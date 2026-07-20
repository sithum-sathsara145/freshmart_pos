{{-- hrm/self/index.blade.php --}}
@extends('layouts.app')
@section('title','My HR')
@section('page-title','My HR')
@section('content')
<div style="padding:14px 16px;max-width:900px">

@include('hrm.self._tabs', ['active' => 'index'])

{{-- Clock card --}}
<div x-data="myClock()" style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <div style="width:36px;height:36px;background:var(--info-soft);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--info);flex-shrink:0">
        <i class="ti ti-clock-hour-8" style="font-size:18px"></i>
    </div>
    <div style="flex:1;min-width:170px">
        <div style="font-size:13px;color:var(--text);font-weight:500">{{ $staff->name }}</div>
        <div style="font-size:11px;color:var(--text-3)" x-text="statusText">
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
            style="height:32px;padding:0 14px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;cursor:pointer">Check in</button>
        <button type="button" @click="clock('out')" :disabled="busy"
            style="height:32px;padding:0 14px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;cursor:pointer">Check out</button>
    </div>
    @endcan
    <div x-show="error" x-cloak x-text="error" style="font-size:11px;color:var(--danger-text);width:100%"></div>
</div>

{{-- This month --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:12px">
    @foreach([
        ['Days present',$summary['present'],'var(--success)'],
        ['On leave',$summary['leave'],'var(--warning)'],
        ['Absent',$summary['absent'],'var(--danger)'],
        ['Hours',number_format($summary['hours'],1),'var(--text)'],
        ['Overtime',number_format($summary['ot'],1).'h','var(--primary-text)'],
    ] as [$l,$v,$c])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:3px">{{ $l }}</div>
        <div style="font-size:17px;font-weight:500;color:{{ $c }}">{{ $v }}</div>
    </div>
    @endforeach
</div>
<div style="font-size:10px;color:var(--text-4);margin:-6px 0 14px 2px">This month · {{ now()->format('F Y') }}</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">

    {{-- Leave balance --}}
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:9px">
            <div style="font-size:12px;font-weight:500;color:var(--text-2)">Leave balance {{ $year }}</div>
            <a href="{{ route('my.leave') }}" style="font-size:11px;color:var(--primary-text);text-decoration:none">Request →</a>
        </div>
        @foreach($balances as $b)
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:5px 0;border-bottom:.5px solid var(--surface-3)">
            <span style="color:var(--text-3)">{{ $b['label'] }}</span>
            @if($b['tracked'])
            <span>
                <span style="color:{{ $b['remaining'] <= 0 ? 'var(--danger)' : 'var(--success)' }};font-weight:500">{{ rtrim(rtrim(number_format($b['remaining'],1),'0'),'.') }}</span>
                <span style="color:var(--text-4)"> / {{ rtrim(rtrim(number_format($b['entitled'],1),'0'),'.') }} days</span>
            </span>
            @else
            <span style="color:var(--text-3);font-size:11px">unpaid</span>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Latest payslip --}}
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:9px">
            <div style="font-size:12px;font-weight:500;color:var(--text-2)">Latest payslip</div>
            <a href="{{ route('my.payslips') }}" style="font-size:11px;color:var(--primary-text);text-decoration:none">All payslips →</a>
        </div>
        @if($lastPayslip)
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:5px 0;border-bottom:.5px solid var(--surface-3)">
            <span style="color:var(--text-3)">Period</span><span style="color:var(--text)">{{ $lastPayslip->periodLabel() }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:5px 0;border-bottom:.5px solid var(--surface-3)">
            <span style="color:var(--text-3)">Net pay</span><span style="color:var(--success);font-weight:500">Rs. {{ number_format($lastPayslip->net_salary, 2) }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:5px 0">
            <span style="color:var(--text-3)">Status</span>
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $lastPayslip->status === 'paid' ? 'var(--success-soft)' : 'var(--warning-soft)' }};color:{{ $lastPayslip->status === 'paid' ? 'var(--success)' : 'var(--warning)' }}">{{ ucfirst($lastPayslip->status) }}</span>
        </div>
        <a href="{{ route('my.payslip', $lastPayslip->id) }}" style="display:block;text-align:center;margin-top:9px;height:30px;line-height:30px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:11px;text-decoration:none">View payslip</a>
        @else
        <div style="color:var(--text-3);font-size:12px;padding:6px 0">No payslip issued yet.</div>
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
