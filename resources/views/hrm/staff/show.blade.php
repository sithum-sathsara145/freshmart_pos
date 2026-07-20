{{-- hrm/staff/show.blade.php --}}
@extends('layouts.app')
@section('title',$staff->name)
@section('page-title','Staff — '.$staff->name)
@section('content')
<div style="padding:14px 16px;max-width:820px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('hrm.staff.index') }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-left" style="font-size:12px"></i>Back
    </a>
    <a href="{{ route('hrm.staff.edit',$staff) }}" style="height:32px;padding:0 12px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-pencil" style="font-size:12px"></i>Edit
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:12px">
<div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Details</div>
        @foreach([
            ['Job title',$staff->role ?? '—'],
            ['Phone',$staff->phone ?? '—'],
            ['Email',$staff->email ?? '—'],
            ['Address',$staff->address ?? '—'],
            ['Basic salary','Rs. '.number_format($staff->basic_salary,2)],
            ['Joined',$staff->join_date?->format('d M Y') ?? '—'],
            ['Branch',$staff->branch?->name ?? '—'],
        ] as [$l,$v])
        <div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;border-bottom:.5px solid var(--surface-3);font-size:12px">
            <span style="color:var(--text-3);white-space:nowrap">{{ $l }}</span><span style="color:var(--text);text-align:right">{{ $v }}</span>
        </div>
        @endforeach

        {{-- Job title (above) is an HR label; the system role below is what the app
             actually authorises. They're deliberately different things. --}}
        <div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;border-bottom:.5px solid var(--surface-3);font-size:12px">
            <span style="color:var(--text-3);white-space:nowrap">Login account</span>
            <span style="text-align:right">
                @if($staff->user)
                <span style="color:var(--text)">{{ $staff->user->email }}</span>
                @if($staff->user->roles->isNotEmpty())
                <span style="display:block;font-size:10px;color:var(--primary-text);margin-top:2px">
                    system role: {{ $staff->user->roles->pluck('name')->map(fn($r) => str_replace('_',' ',$r))->implode(', ') }}
                </span>
                @endif
                @else
                <span style="color:var(--text-3)">Not linked</span>
                @can('hrm.staff.manage')
                <a href="{{ route('hrm.staff.edit',$staff) }}" style="display:block;font-size:10px;color:var(--primary-text);margin-top:2px;text-decoration:none">Link an account →</a>
                @endcan
                @endif
            </span>
        </div>

        <div style="margin-top:10px;text-align:center">
            <span style="font-size:11px;padding:4px 14px;border-radius:10px;font-weight:500;background:{{ $staff->status==='active'?'var(--success-soft)':'var(--danger-soft)' }};color:{{ $staff->status==='active'?'var(--success)':'var(--danger-text)' }}">{{ strtoupper($staff->status) }}</span>
        </div>
    </div>

    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:8px">This month's payroll</div>
        @if($currentPayroll)
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0"><span style="color:var(--text-3)">Net salary</span><span style="color:var(--success);font-weight:500">Rs. {{ number_format($currentPayroll->net_salary ?? 0,2) }}</span></div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0"><span style="color:var(--text-3)">Status</span><span style="color:var(--text-2)">{{ ucfirst($currentPayroll->status ?? '—') }}</span></div>
        @else
        <div style="color:var(--text-3);font-size:12px">Not generated yet.</div>
        @endif
    </div>

    {{-- Entitlement is stored; "used" is always summed from approved requests, so
         the remaining figure can't drift away from the requests themselves. --}}
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-top:12px"
         x-data="{ editing: false }">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <div style="font-size:12px;font-weight:500;color:var(--text-2)">Leave balance {{ $year }}</div>
            @can('hrm.staff.manage')
            <button type="button" @click="editing = !editing"
                style="background:none;border:none;color:var(--primary-text);font-size:11px;cursor:pointer;padding:0"
                x-text="editing ? 'Cancel' : 'Adjust'"></button>
            @endcan
        </div>

        <div x-show="!editing">
            @foreach($leaveBalances as $b)
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding:5px 0;border-bottom:.5px solid var(--surface-3)">
                <span style="color:var(--text-3)">{{ $b['label'] }}</span>
                @if($b['tracked'])
                <span>
                    <span style="color:{{ $b['remaining'] <= 0 ? 'var(--danger)' : 'var(--success)' }};font-weight:500">{{ rtrim(rtrim(number_format($b['remaining'],1),'0'),'.') }}</span>
                    <span style="color:var(--text-4)"> / {{ rtrim(rtrim(number_format($b['entitled'],1),'0'),'.') }}</span>
                    @if($b['used'] > 0)
                    <span style="color:var(--text-4);font-size:10px"> · {{ rtrim(rtrim(number_format($b['used'],1),'0'),'.') }} taken</span>
                    @endif
                </span>
                @else
                <span style="color:var(--text-3);font-size:11px">unpaid · {{ rtrim(rtrim(number_format($b['used'],1),'0'),'.') }} taken</span>
                @endif
            </div>
            @endforeach
        </div>

        @can('hrm.staff.manage')
        <form x-show="editing" x-cloak method="POST" action="{{ route('hrm.staff.entitlements', $staff) }}">
            @csrf @method('PUT')
            <input type="hidden" name="year" value="{{ $year }}">
            @foreach($leaveBalances as $b)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;padding:4px 0">
                <label style="color:var(--text-3);font-size:12px">{{ $b['label'] }}</label>
                <input type="number" name="days[{{ $b['type'] }}]" value="{{ rtrim(rtrim(number_format($b['entitled'],1),'0'),'.') }}"
                    min="0" max="365" step="0.5"
                    style="width:74px;background:var(--bg);border:.5px solid var(--border);border-radius:5px;color:var(--text);font-size:12px;padding:4px 7px;outline:none;text-align:right">
            </div>
            @endforeach
            <button type="submit" style="width:100%;margin-top:8px;height:30px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:11px;cursor:pointer">Save entitlement</button>
        </form>
        @endcan
    </div>
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Recent attendance</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            <th style="padding:6px 0;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Date</th>
            <th style="padding:6px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">In</th>
            <th style="padding:6px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">Out</th>
            <th style="padding:6px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Status</th>
        </tr></thead>
        <tbody>
        @forelse($staff->attendance as $a)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:7px 0;color:var(--text)">{{ \Carbon\Carbon::parse($a->date)->format('d M Y') }}</td>
            <td style="padding:7px 6px;text-align:center;color:var(--text-2)">{{ $a->time_in ?? '—' }}</td>
            <td style="padding:7px 6px;text-align:center;color:var(--text-2)">{{ $a->time_out ?? '—' }}</td>
            <td style="padding:7px 0;text-align:right">
                <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['present'=>'var(--success-soft)','absent'=>'var(--danger-soft)','late'=>'var(--warning-soft)','half_day'=>'var(--info-soft)'][$a->status] ?? 'var(--surface-2)' }};color:{{ ['present'=>'var(--success)','absent'=>'var(--danger-text)','late'=>'var(--warning)','half_day'=>'var(--info)'][$a->status] ?? 'var(--text-2)' }}">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:24px;text-align:center;color:var(--text-4)">No attendance records</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
</div>
@endsection
