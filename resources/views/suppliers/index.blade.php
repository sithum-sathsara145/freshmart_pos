{{-- suppliers/index.blade.php --}}
@extends('layouts.app')
@section('title','Suppliers')
@section('page-title','Suppliers')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;gap:8px;margin-bottom:12px">
    <form method="GET" style="display:flex;gap:8px;flex:1">
        <div style="flex:1;display:flex;align-items:center;gap:7px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;padding:0 10px;height:34px">
            <i class="ti ti-search" style="font-size:13px;color:var(--text-3)"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Search supplier name, phone..."
                style="background:none;border:none;outline:none;color:var(--text);font-size:12px;width:100%">
        </div>
        <button type="submit" style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Search</button>
    </form>
    <a href="{{ route('suppliers.create') }}" style="height:34px;padding:0 14px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>Add Supplier
    </a>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Supplier','Contact','Phone','City','Total purchases','Balance due','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($suppliers as $s)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px;color:var(--text);font-weight:500">{{ $s->name }}</td>
        <td style="padding:9px 12px;color:var(--text-2)">{{ $s->contact_person ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $s->phone ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $s->city ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text)">Rs. {{ number_format($s->total_purchases) }}</td>
        <td style="padding:9px 12px;color:{{ $s->balance_due > 0 ? 'var(--danger)' : 'var(--success)' }};font-weight:500">Rs. {{ number_format($s->balance_due) }}</td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:3px">
                <a href="{{ route('suppliers.show',$s) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                <a href="{{ route('suppliers.edit',$s) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:32px;text-align:center;color:var(--text-4)">No suppliers found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $suppliers->links() }}</div>
</div>
@endsection
