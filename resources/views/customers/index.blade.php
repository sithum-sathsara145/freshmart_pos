{{-- customers/index.blade.php --}}
@extends('layouts.app')
@section('title','Customers')
@section('page-title','Customers')
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;gap:8px;margin-bottom:12px;align-items:center">
    <form method="GET" style="display:flex;gap:8px;flex:1">
        <div style="flex:1;display:flex;align-items:center;gap:7px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;padding:0 10px;height:34px">
            <i class="ti ti-search" style="font-size:13px;color:var(--text-3)"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Search name, phone..." style="background:none;border:none;outline:none;color:var(--text);font-size:12px;width:100%">
        </div>
        <button type="submit" style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Search</button>
    </form>
    <a href="{{ route('customers.create') }}" style="height:34px;padding:0 14px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>Add Customer
    </a>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid var(--border)">
        @foreach(['Name','Phone','Email','Total purchases','Loyalty pts','Level','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($customers as $c)
    <tr style="border-bottom:.5px solid var(--surface-3)">
        <td style="padding:9px 12px">
            <div style="display:flex;align-items:center;gap:8px">
                <div style="width:28px;height:28px;background:var(--info-soft);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:500;color:var(--info)">{{ strtoupper(substr($c->name,0,2)) }}</div>
                <span style="color:var(--text);font-weight:500">{{ $c->name }}</span>
            </div>
        </td>
        <td style="padding:9px 12px;color:var(--text-2)">{{ $c->phone ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text-3)">{{ $c->email ?? '—' }}</td>
        <td style="padding:9px 12px;color:var(--text)">Rs. {{ number_format($c->total_purchases) }}</td>
        <td style="padding:9px 12px;color:var(--warning);font-weight:500">{{ number_format($c->loyalty_points) }}</td>
        <td style="padding:9px 12px">
            @php $lvl=['bronze'=>['var(--danger-soft)','var(--danger-text)'],'silver'=>['var(--info-soft)','var(--info)'],'gold'=>['var(--warning-soft)','var(--warning)'],'platinum'=>['var(--primary-soft)','var(--primary-text)']][$c->loyalty_level??'bronze']??['var(--surface-2)','var(--text-2)']; @endphp
            <span style="font-size:10px;padding:2px 8px;border-radius:10px;background:{{ $lvl[0] }};color:{{ $lvl[1] }}">{{ ucfirst($c->loyalty_level ?? 'bronze') }}</span>
        </td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:3px">
                <a href="{{ route('customers.show',$c) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-eye" style="font-size:12px"></i></a>
                <a href="{{ route('customers.edit',$c) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:32px;text-align:center;color:var(--text-4)">No customers found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $customers->links() }}</div>
</div>
@endsection
