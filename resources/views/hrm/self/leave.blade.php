{{-- hrm/self/leave.blade.php --}}
@extends('layouts.app')
@section('title','My Leave')
@section('page-title','My Leave')
@section('content')
@php $inp = 'width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none;box-sizing:border-box'; @endphp
<div style="padding:14px 16px;max-width:900px" x-data="myLeave()">

@include('hrm.self._tabs', ['active' => 'leave'])

<div style="display:grid;grid-template-columns:300px 1fr;gap:12px;align-items:start">

    @can('hrm.self.leave')
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Request leave</div>
        <form method="POST" action="{{ route('my.leave.store') }}">
        @csrf
        <div style="margin-bottom:9px">
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Type</label>
            <select name="type" x-model="type" required style="{{ $inp }}">
                @foreach(['annual'=>'Annual','sick'=>'Sick','casual'=>'Casual','other'=>'Other (unpaid)'] as $v=>$l)
                <option value="{{ $v }}" @selected(old('type','casual') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:9px">
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">From</label>
            <input type="date" name="from_date" x-model="from" value="{{ old('from_date', today()->toDateString()) }}" required style="{{ $inp }}">
        </div>
        <div style="margin-bottom:9px">
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">To</label>
            <input type="date" name="to_date" x-model="to" value="{{ old('to_date', today()->toDateString()) }}" required style="{{ $inp }}">
        </div>
        <div style="margin-bottom:9px">
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Reason</label>
            <input type="text" name="reason" value="{{ old('reason') }}" placeholder="optional" style="{{ $inp }}">
        </div>
        <div x-show="warning" x-cloak x-text="warning"
             style="margin-bottom:9px;background:#451a03;border:.5px solid #854d0e;border-radius:6px;padding:7px 9px;font-size:11px;color:#fbbf24"></div>
        <button type="submit" style="width:100%;height:34px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">Submit request</button>
        </form>
    </div>
    @endcan

    <div>
        <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
            <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:9px">Balance {{ $year }}</div>
            <div style="display:flex;gap:18px;flex-wrap:wrap">
                @foreach($balances as $b)
                <div>
                    <div style="font-size:10px;color:#64748b">{{ $b['label'] }}</div>
                    @if($b['tracked'])
                    <div style="font-size:15px;font-weight:500;color:{{ $b['remaining'] <= 0 ? '#f87171' : '#4ade80' }}">
                        {{ rtrim(rtrim(number_format($b['remaining'],1),'0'),'.') }}<span style="font-size:11px;color:#4a5568"> / {{ rtrim(rtrim(number_format($b['entitled'],1),'0'),'.') }}</span>
                    </div>
                    @else
                    <div style="font-size:12px;color:#64748b;padding-top:3px">unpaid</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr style="border-bottom:.5px solid #2a2d3a">
                @foreach(['Type','From','To','Days','Status',''] as $h)
                <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
                @endforeach
            </tr></thead>
            <tbody>
            @forelse($requests as $l)
            <tr style="border-bottom:.5px solid #1a1d2a">
                <td style="padding:8px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#312e81;color:#a5b4fc">{{ ucfirst($l->type) }}</span></td>
                <td style="padding:8px 12px;color:#64748b">{{ $l->from_date?->format('d M') }}</td>
                <td style="padding:8px 12px;color:#64748b">{{ $l->to_date?->format('d M Y') }}</td>
                <td style="padding:8px 12px;color:#e2e8f0">{{ $l->days }}</td>
                <td style="padding:8px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['pending'=>'#451a03','approved'=>'#14532d','rejected'=>'#7f1d1d'][$l->status]??'#1e2130' }};color:{{ ['pending'=>'#fb923c','approved'=>'#4ade80','rejected'=>'#fca5a5'][$l->status]??'#94a3b8' }}">{{ ucfirst($l->status) }}</span></td>
                <td style="padding:8px 12px">
                    @can('hrm.self.leave')
                    @if($l->status === 'pending')
                    <form method="POST" action="{{ route('my.leave.destroy', $l->id) }}" onsubmit="return confirm('Withdraw this request?')">
                        @csrf @method('DELETE')
                        <button type="submit" title="Withdraw" style="width:24px;height:24px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;color:#f87171;cursor:pointer"><i class="ti ti-x" style="font-size:12px"></i></button>
                    </form>
                    @endif
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="padding:26px;text-align:center;color:#4a5568">No leave requests yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
function myLeave() {
    return {
        balances: {{ Js::from($balances) }},
        holidays: {{ Js::from(\App\Models\Holiday::whereYear('date', $year)->pluck('date')->map(fn($d) => $d->toDateString())) }},
        type: @js(old('type', 'casual')),
        from: @js(old('from_date', today()->toDateString())),
        to:   @js(old('to_date', today()->toDateString())),

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
            const row = Object.values(this.balances).find(r => r.type === this.type);
            if (!row) return '';
            const d = this.days;
            if (d === 0) return 'That range is entirely holidays — nothing to record.';
            if (row.tracked && d > row.remaining) {
                return `You have ${row.remaining} ${row.type} day(s) left — this is for ${d}.`;
            }
            return `${d} day(s) will be deducted.`;
        },
    };
}
</script>
@endpush
@endsection
