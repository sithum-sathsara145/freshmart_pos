{{-- hrm/staff/index.blade.php --}}
@extends('layouts.app')
@section('title','Staff Members')
@section('page-title','Staff Members')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;gap:8px;margin-bottom:12px">
    <form method="GET" style="display:flex;gap:8px;flex:1">
        <div style="flex:1;display:flex;align-items:center;gap:7px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;padding:0 10px;height:34px">
            <i class="ti ti-search" style="font-size:13px;color:#64748b"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Search name, phone..." style="background:none;border:none;outline:none;color:#e2e8f0;font-size:12px;width:100%">
        </div>
        <select name="role" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
            <option value="">All roles</option>
            @foreach(['Cashier','Supervisor','Stock Manager','Delivery','Cleaning'] as $r)
            <option value="{{ $r }}" {{ request('role')===$r?'selected':'' }}>{{ $r }}</option>
            @endforeach
        </select>
        <button type="submit" style="height:34px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">Filter</button>
    </form>
    <a href="{{ route('hrm.staff.create') }}" style="height:34px;padding:0 14px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>Add Staff
    </a>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Name','Role','Phone','Salary','Joined','Status','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($staff as $s)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px">
            <div style="display:flex;align-items:center;gap:8px">
                <div style="width:28px;height:28px;background:#1e3a5f;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:500;color:#60a5fa">{{ strtoupper(substr($s->name,0,2)) }}</div>
                <span style="color:#e2e8f0;font-weight:500">{{ $s->name }}</span>
            </div>
        </td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:#312e81;color:#a5b4fc">{{ $s->role }}</span></td>
        <td style="padding:9px 12px;color:#64748b">{{ $s->phone ?? '—' }}</td>
        <td style="padding:9px 12px;color:#e2e8f0">Rs. {{ number_format($s->basic_salary) }}</td>
        <td style="padding:9px 12px;color:#64748b">{{ \Carbon\Carbon::parse($s->join_date)->format('M Y') }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $s->status==='active'?'#14532d':'#1e2130' }};color:{{ $s->status==='active'?'#4ade80':'#94a3b8' }}">{{ ucfirst($s->status) }}</span></td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:3px">
                <a href="{{ route('hrm.staff.show',$s) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                <a href="{{ route('hrm.staff.edit',$s) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:32px;text-align:center;color:#4a5568">No staff members found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $staff->links() }}</div>
</div>
@endsection
