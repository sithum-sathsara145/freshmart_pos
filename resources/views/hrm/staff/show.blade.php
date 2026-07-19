{{-- hrm/staff/show.blade.php --}}
@extends('layouts.app')
@section('title',$staff->name)
@section('page-title','Staff — '.$staff->name)
@section('content')
<div style="padding:14px 16px;max-width:820px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('hrm.staff.index') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-arrow-left" style="font-size:12px"></i>Back
    </a>
    <a href="{{ route('hrm.staff.edit',$staff) }}" style="height:32px;padding:0 12px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none">
        <i class="ti ti-pencil" style="font-size:12px"></i>Edit
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:12px">
<div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Details</div>
        @foreach([
            ['Job title',$staff->role ?? '—'],
            ['Phone',$staff->phone ?? '—'],
            ['Email',$staff->email ?? '—'],
            ['Address',$staff->address ?? '—'],
            ['Basic salary','Rs. '.number_format($staff->basic_salary,2)],
            ['Joined',$staff->join_date?->format('d M Y') ?? '—'],
            ['Branch',$staff->branch?->name ?? '—'],
        ] as [$l,$v])
        <div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
            <span style="color:#64748b;white-space:nowrap">{{ $l }}</span><span style="color:#e2e8f0;text-align:right">{{ $v }}</span>
        </div>
        @endforeach

        {{-- Job title (above) is an HR label; the system role below is what the app
             actually authorises. They're deliberately different things. --}}
        <div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
            <span style="color:#64748b;white-space:nowrap">Login account</span>
            <span style="text-align:right">
                @if($staff->user)
                <span style="color:#e2e8f0">{{ $staff->user->email }}</span>
                @if($staff->user->roles->isNotEmpty())
                <span style="display:block;font-size:10px;color:#a5b4fc;margin-top:2px">
                    system role: {{ $staff->user->roles->pluck('name')->map(fn($r) => str_replace('_',' ',$r))->implode(', ') }}
                </span>
                @endif
                @else
                <span style="color:#64748b">Not linked</span>
                @can('hrm.staff.manage')
                <a href="{{ route('hrm.staff.edit',$staff) }}" style="display:block;font-size:10px;color:#a5b4fc;margin-top:2px;text-decoration:none">Link an account →</a>
                @endcan
                @endif
            </span>
        </div>

        <div style="margin-top:10px;text-align:center">
            <span style="font-size:11px;padding:4px 14px;border-radius:10px;font-weight:500;background:{{ $staff->status==='active'?'#14532d':'#7f1d1d' }};color:{{ $staff->status==='active'?'#4ade80':'#fca5a5' }}">{{ strtoupper($staff->status) }}</span>
        </div>
    </div>

    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:8px">This month's payroll</div>
        @if($currentPayroll)
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0"><span style="color:#64748b">Net salary</span><span style="color:#4ade80;font-weight:500">Rs. {{ number_format($currentPayroll->net_salary ?? 0,2) }}</span></div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0"><span style="color:#64748b">Status</span><span style="color:#94a3b8">{{ ucfirst($currentPayroll->status ?? '—') }}</span></div>
        @else
        <div style="color:#64748b;font-size:12px">Not generated yet.</div>
        @endif
    </div>
</div>

<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Recent attendance</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:6px 0;text-align:left;color:#64748b;font-weight:500;font-size:11px">Date</th>
            <th style="padding:6px;text-align:center;color:#64748b;font-weight:500;font-size:11px">In</th>
            <th style="padding:6px;text-align:center;color:#64748b;font-weight:500;font-size:11px">Out</th>
            <th style="padding:6px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Status</th>
        </tr></thead>
        <tbody>
        @forelse($staff->attendance as $a)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px 0;color:#e2e8f0">{{ \Carbon\Carbon::parse($a->date)->format('d M Y') }}</td>
            <td style="padding:7px 6px;text-align:center;color:#94a3b8">{{ $a->time_in ?? '—' }}</td>
            <td style="padding:7px 6px;text-align:center;color:#94a3b8">{{ $a->time_out ?? '—' }}</td>
            <td style="padding:7px 0;text-align:right">
                <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ ['present'=>'#14532d','absent'=>'#7f1d1d','late'=>'#451a03','half_day'=>'#1e3a5f'][$a->status] ?? '#1e2130' }};color:{{ ['present'=>'#4ade80','absent'=>'#fca5a5','late'=>'#fb923c','half_day'=>'#60a5fa'][$a->status] ?? '#94a3b8' }}">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:24px;text-align:center;color:#4a5568">No attendance records</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
</div>
@endsection
