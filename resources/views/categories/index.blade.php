{{-- categories/index.blade.php --}}
@extends('layouts.app')
@section('title','Categories')
@section('page-title','Categories')
@section('content')
<div style="padding:14px 16px;display:grid;grid-template-columns:320px 1fr;gap:14px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;align-self:start">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Add category</div>
    <form method="POST" action="{{ route('categories.store') }}">
    @csrf
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Category name *</label><input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Beverages" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none"></div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Parent category</label>
        <select name="parent_id" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="">— None (top level) —</option>
            @foreach($parents as $p)
            <option value="{{ $p->id }}" {{ old('parent_id')==$p->id?'selected':'' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="margin-bottom:12px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Description</label><textarea name="description" rows="2" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none;resize:vertical">{{ old('description') }}</textarea></div>
    <button type="submit" style="width:100%;height:36px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-plus" style="font-size:13px;margin-right:4px"></i>Add Category</button>
    @error('name')<div style="color:#f87171;font-size:11px;margin-top:8px">{{ $message }}</div>@enderror
    </form>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8">All categories</div>
        <form method="GET" style="display:flex;align-items:center;gap:7px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:0 10px;height:30px">
            <i class="ti ti-search" style="font-size:12px;color:#64748b"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Search..." style="background:none;border:none;outline:none;color:#e2e8f0;font-size:12px;width:130px">
        </form>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            @foreach(['Name','Parent','Products','Actions'] as $h)
            <th style="padding:7px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @forelse($categories as $c)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px 10px;color:#e2e8f0;font-weight:500">{{ $c->name }}</td>
            <td style="padding:7px 10px;color:#64748b">{{ $c->parent->name ?? '—' }}</td>
            <td style="padding:7px 10px;color:#94a3b8">{{ $c->products_count }}</td>
            <td style="padding:7px 10px">
                <div style="display:flex;gap:3px">
                    <a href="{{ route('categories.edit',$c) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a>
                    <form method="POST" action="{{ route('categories.destroy',$c) }}" onsubmit="return confirm('Delete category?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#f87171;cursor:pointer"><i class="ti ti-trash" style="font-size:12px"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:24px;text-align:center;color:#4a5568">No categories yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:12px">{{ $categories->links() }}</div>
</div>
</div>
@endsection
