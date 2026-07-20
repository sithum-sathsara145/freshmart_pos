{{-- brands/index.blade.php --}}
@extends('layouts.app')
@section('title','Brands')
@section('page-title','Brands')
@section('content')
<div style="padding:14px 16px;display:grid;grid-template-columns:320px 1fr;gap:14px">
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;align-self:start">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Add brand</div>
    <form method="POST" action="{{ route('brands.store') }}">
    @csrf
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Brand name *</label><input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Anchor" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none"></div>
    <div style="margin-bottom:12px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Description</label><textarea name="description" rows="2" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none;resize:vertical">{{ old('description') }}</textarea></div>
    <button type="submit" style="width:100%;height:36px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-plus" style="font-size:13px;margin-right:4px"></i>Add Brand</button>
    @error('name')<div style="color:var(--danger);font-size:11px;margin-top:8px">{{ $message }}</div>@enderror
    </form>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2)">All brands</div>
        <form method="GET" style="display:flex;align-items:center;gap:7px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:0 10px;height:30px">
            <i class="ti ti-search" style="font-size:12px;color:var(--text-3)"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Search..." style="background:none;border:none;outline:none;color:var(--text);font-size:12px;width:130px">
        </form>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            @foreach(['Name','Description','Products','Actions'] as $h)
            <th style="padding:7px 10px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @forelse($brands as $b)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:7px 10px;color:var(--text);font-weight:500">{{ $b->name }}</td>
            <td style="padding:7px 10px;color:var(--text-3)">{{ $b->description ?? '—' }}</td>
            <td style="padding:7px 10px;color:var(--text-2)">{{ $b->products_count }}</td>
            <td style="padding:7px 10px">
                <div style="display:flex;gap:3px">
                    <a href="{{ route('brands.edit',$b) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a>
                    <form method="POST" action="{{ route('brands.destroy',$b) }}" onsubmit="return confirm('Delete brand?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--danger);cursor:pointer"><i class="ti ti-trash" style="font-size:12px"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:24px;text-align:center;color:var(--text-4)">No brands yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:12px">{{ $brands->links() }}</div>
</div>
</div>
@endsection
