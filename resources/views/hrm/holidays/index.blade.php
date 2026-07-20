{{-- hrm/holidays/index.blade.php --}}
@extends('layouts.app')
@section('title','Holidays')
@section('page-title','Holidays')
@section('content')
<div style="padding:14px 16px;display:grid;grid-template-columns:320px 1fr;gap:14px">
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;align-self:start">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Add holiday</div>
    <form method="POST" action="{{ route('hrm.holidays.store') }}">
    @csrf
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Holiday name *</label><input type="text" name="name" required placeholder="e.g. Sinhala New Year" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none"></div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Date *</label><input type="date" name="date" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none"></div>
    <div style="margin-bottom:12px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Type</label>
        <select name="type" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            <option value="public">Public holiday</option><option value="company">Company holiday</option>
        </select>
    </div>
    <button type="submit" style="width:100%;height:36px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-plus" style="font-size:13px;margin-right:4px"></i>Add Holiday</button>
    </form>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2)">Holidays in {{ $year }}</div>
        {{-- The list used to paginate at 30 with no links, silently hiding the rest.
             A year's holidays fit on one page, so switch years instead. --}}
        <form method="GET" style="margin-left:auto;display:flex;gap:6px;align-items:center">
            <select name="year" onchange="this.form.submit()" style="height:28px;background:var(--bg);border:.5px solid var(--border);border-radius:5px;color:var(--text);font-size:11px;padding:0 8px;outline:none">
                @foreach($years->contains($year) ? $years : $years->push($year)->sortDesc() as $y)
                <option value="{{ $y }}" @selected((int) $y === (int) $year)>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>
    @if($upcoming->count())
    <div style="font-size:11px;color:var(--text-3);margin-bottom:8px">
        Next up: {{ $upcoming->take(3)->map(fn($u) => $u->name.' ('.$u->date->format('d M').')')->implode(' · ') }}
    </div>
    @endif
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)"><th style="padding:7px 10px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Holiday</th><th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">Date</th><th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">Type</th><th style="padding:7px 10px;color:var(--text-3);font-weight:500;font-size:11px">Action</th></tr></thead>
        <tbody>
        @forelse($holidays as $h)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:7px 10px;color:var(--text);font-weight:500">{{ $h->name }}</td>
            <td style="padding:7px 10px;color:var(--text-3)">{{ $h->date->format('D, d M Y') }}</td>
            <td style="padding:7px 10px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $h->type==='public'?'var(--danger-soft)':'var(--primary-soft)' }};color:{{ $h->type==='public'?'var(--danger-text)':'var(--primary-text)' }}">{{ ucfirst($h->type) }}</span></td>
            <td style="padding:7px 10px">
                <form method="POST" action="{{ route('hrm.holidays.destroy',$h) }}" onsubmit="return confirm('Remove holiday?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--danger);cursor:pointer"><i class="ti ti-trash" style="font-size:12px"></i></button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:24px;text-align:center;color:var(--text-4)">No holidays</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
