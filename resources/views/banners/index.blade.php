{{-- banners/index.blade.php --}}
@extends('layouts.app')
@section('title','Banners')
@section('page-title','Website Banners')
@section('content')
<div style="padding:14px 16px;display:grid;grid-template-columns:320px 1fr;gap:14px">
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;align-self:start">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Add banner</div>
    <form method="POST" action="{{ route('website.banners.store') }}">
    @csrf
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Title</label><input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. New Year Sale" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none"></div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Image URL *</label><input type="text" name="image" value="{{ old('image') }}" required placeholder="https://..." style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none"></div>
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Link (optional)</label><input type="text" name="link" value="{{ old('link') }}" placeholder="/products or https://..." style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none"></div>
    <div style="display:flex;gap:8px;margin-bottom:12px">
        <div style="flex:1"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Sort order</label><input type="number" name="sort_order" value="{{ old('sort_order',0) }}" min="0" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none"></div>
        <div style="flex:1"><label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Status</label>
            <select name="status" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
                <option value="active">Active</option><option value="inactive">Inactive</option>
            </select>
        </div>
    </div>
    <button type="submit" style="width:100%;height:36px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-plus" style="font-size:13px;margin-right:4px"></i>Add Banner</button>
    @error('image')<div style="color:var(--danger);font-size:11px;margin-top:8px">{{ $message }}</div>@enderror
    </form>
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">All banners</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            @foreach(['Preview','Title','Order','Status','Actions'] as $h)
            <th style="padding:7px 10px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @forelse($banners as $b)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:7px 10px"><img src="{{ $b->image }}" alt="" style="width:64px;height:32px;object-fit:cover;border-radius:4px;background:var(--bg);border:.5px solid var(--border)"></td>
            <td style="padding:7px 10px;color:var(--text);font-weight:500">{{ $b->title ?? '—' }}</td>
            <td style="padding:7px 10px;color:var(--text-2)">{{ $b->sort_order }}</td>
            <td style="padding:7px 10px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:{{ $b->status==='active'?'var(--success-soft)':'var(--danger-soft-2)' }};color:{{ $b->status==='active'?'var(--success)':'var(--danger-text)' }}">{{ ucfirst($b->status) }}</span></td>
            <td style="padding:7px 10px">
                <div style="display:flex;gap:3px">
                    <a href="{{ route('website.banners.edit',$b) }}" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a>
                    <form method="POST" action="{{ route('website.banners.destroy',$b) }}" onsubmit="return confirm('Delete banner?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--danger);cursor:pointer"><i class="ti ti-trash" style="font-size:12px"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" style="padding:24px;text-align:center;color:var(--text-4)">No banners yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:12px">{{ $banners->links() }}</div>
</div>
</div>
@endsection
