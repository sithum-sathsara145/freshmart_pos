{{-- hrm/leaves/create.blade.php --}}
@extends('layouts.app')
@section('title','New Leave Request')
@section('page-title','New Leave Request')
@section('content')
@php $inp = 'width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none;box-sizing:border-box'; @endphp
<div style="padding:14px 16px;max-width:480px" x-data="leaveForm()">
<form method="POST" action="{{ route('hrm.leaves.store') }}">
@csrf
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:16px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Leave details</div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Staff member *</label>
        <select name="staff_id" x-model="staffId" required style="{{ $inp }}">
            <option value="">— Select staff —</option>
            @foreach($staff as $s)
            <option value="{{ $s->id }}" {{ old('staff_id')==$s->id?'selected':'' }}>{{ $s->name }} ({{ $s->role }})</option>
            @endforeach
        </select>
    </div>

    {{-- Remaining days for whoever is selected. Server re-checks on submit; this
         is only here so nobody fills in a request they can't have. --}}
    <div x-show="balance" x-cloak style="margin-bottom:10px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:9px 11px">
        <div style="font-size:10px;color:#64748b;margin-bottom:6px">Balance for {{ $year }}</div>
        <div style="display:flex;gap:14px;flex-wrap:wrap">
            <template x-for="row in (balance || [])" :key="row.type">
                <div style="font-size:11px">
                    <span style="color:#64748b" x-text="row.label"></span>
                    <span style="margin-left:4px;font-weight:500"
                          :style="row.tracked && row.remaining <= 0 ? 'color:#f87171' : (row.tracked ? 'color:#4ade80' : 'color:#94a3b8')"
                          x-text="row.tracked ? (row.remaining + ' / ' + row.entitled) : 'unpaid'"></span>
                </div>
            </template>
        </div>
    </div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Leave type *</label>
        <select name="type" x-model="type" required style="{{ $inp }}">
            @foreach(['annual'=>'Annual','sick'=>'Sick','casual'=>'Casual','other'=>'Other (unpaid)'] as $v=>$l)
            <option value="{{ $v }}" {{ old('type','casual')===$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">From *</label>
            <input type="date" name="from_date" x-model="from" value="{{ old('from_date',today()->toDateString()) }}" required style="{{ $inp }}">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">To *</label>
            <input type="date" name="to_date" x-model="to" value="{{ old('to_date',today()->toDateString()) }}" required style="{{ $inp }}">
        </div>
    </div>

    <div x-show="warning" x-cloak style="margin-bottom:10px;background:#451a03;border:.5px solid #854d0e;border-radius:6px;padding:8px 11px;font-size:11px;color:#fbbf24" x-text="warning"></div>

    <div>
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Reason</label>
        <input type="text" name="reason" value="{{ old('reason') }}" placeholder="optional" style="{{ $inp }}">
    </div>
</div>
<div style="display:flex;gap:8px">
    <a href="{{ route('hrm.leaves.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Submit Request</button>
</div>
</form>
</div>

@push('scripts')
<script>
function leaveForm() {
    return {
        balances: {{ Js::from($balances) }},
        holidays: {{ Js::from(\App\Models\Holiday::whereYear('date', $year)->pluck('date')->map(fn($d) => $d->toDateString())) }},
        staffId: @js(old('staff_id', '')),
        type: @js(old('type', 'casual')),
        from: @js(old('from_date', today()->toDateString())),
        to: @js(old('to_date', today()->toDateString())),

        get balance() {
            const b = this.balances[this.staffId];
            return b ? Object.values(b) : null;
        },

        // Mirrors LeaveBalance::countDays() — holidays don't consume leave.
        get days() {
            if (!this.from || !this.to) return 0;
            const start = new Date(this.from), end = new Date(this.to);
            if (end < start) return 0;
            let n = 0;
            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                if (!this.holidays.includes(d.toISOString().slice(0, 10))) n++;
            }
            return n;
        },

        get warning() {
            const b = this.balance;
            if (!b) return '';
            const row = b.find(r => r.type === this.type);
            if (!row) return '';
            const d = this.days;
            if (d === 0) return 'That range is entirely holidays — nothing to record.';
            if (row.tracked && d > row.remaining) {
                return `Only ${row.remaining} ${row.type} day(s) left — this request is for ${d}.`;
            }
            return d > 0 ? `${d} day(s) will be deducted.` : '';
        },
    };
}
</script>
@endpush
@endsection
