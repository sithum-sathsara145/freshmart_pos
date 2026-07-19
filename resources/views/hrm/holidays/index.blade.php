{{-- hrm/holidays/index.blade.php --}}
@extends('layouts.app')
@section('title','Holidays')
@section('page-title','Holidays')
@section('content')
<div style="padding:14px 16px;display:grid;grid-template-columns:320px 1fr;gap:14px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;align-self:start">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Add holiday</div>
    <form method="POST" action="{{ route('hrm.holidays.store') }}">
    @csrf
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Holiday name *</label><input type="text" name="name" required placeholder="e.g. Sinhala New Year" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none"></div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Date *</label><input type="date" name="date" required style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none"></div>
    <div style="margin-bottom:12px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Type</label>
        <select name="type" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="public">Public holiday</option><option value="company">Company holiday</option>
        </select>
    </div>
    <button type="submit" style="width:100%;height:36px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-plus" style="font-size:13px;margin-right:4px"></i>Add Holiday</button>
    </form>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8">Holidays in {{ $year }}</div>
        {{-- The list used to paginate at 30 with no links, silently hiding the rest.
             A year's holidays fit on one page, so switch years instead. --}}
        <form method="GET" style="margin-left:auto;display:flex;gap:6px;align-items:center">
            <select name="year" onchange="this.form.submit()" style="height:28px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:11px;padding:0 8px;outline:none">
                @foreach($years->contains($year) ? $years : $years->push($year)->sortDesc() as $y)
                <option value="{{ $y }}" @selected((int) $y === (int) $year)>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>
    @if($upcoming->count())
    <div style="font-size:11px;color:#64748b;margin-bottom:8px">
        Next up: {{ $upcoming->take(3)->map(fn($u) => $u->name.' ('.$u->date->format('d M').')')->implode(' · ') }}
    </div>
    @endif
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a"><th style="padding:7px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Holiday</th><th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">Date</th><th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">Type</th><th style="padding:7px 10px;color:#64748b;font-weight:500;font-size:11px">Action</th></tr></thead>
        <tbody>
        @forelse($holidays as $h)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px 10px;color:#e2e8f0;font-weight:500">{{ $h->name }}</td>
            <td style="padding:7px 10px;color:#64748b">{{ $h->date->format('D, d M Y') }}</td>
            <td style="padding:7px 10px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $h->type==='public'?'#7f1d1d':'#312e81' }};color:{{ $h->type==='public'?'#fca5a5':'#a5b4fc' }}">{{ ucfirst($h->type) }}</span></td>
            <td style="padding:7px 10px">
                <form method="POST" action="{{ route('hrm.holidays.destroy',$h) }}" onsubmit="return confirm('Remove holiday?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#f87171;cursor:pointer"><i class="ti ti-trash" style="font-size:12px"></i></button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:24px;text-align:center;color:#4a5568">No holidays</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
