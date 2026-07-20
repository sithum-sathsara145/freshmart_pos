{{-- banners/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Banner')
@section('page-title','Edit Banner')
@section('content')
<div style="padding:14px 16px;max-width:500px">
<form method="POST" action="{{ route('website.banners.update',$banner) }}">
@csrf @method('PUT')
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px">
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Title</label>
        <input type="text" name="title" value="{{ old('title',$banner->title) }}" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Image URL *</label>
        <input type="text" name="image" value="{{ old('image',$banner->image) }}" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        @error('image')<div style="color:var(--danger);font-size:11px;margin-top:4px">{{ $message }}</div>@enderror
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Link (optional)</label>
        <input type="text" name="link" value="{{ old('link',$banner->link) }}" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    </div>
    <div style="display:flex;gap:8px">
        <div style="flex:1">
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Sort order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order',$banner->sort_order) }}" min="0" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        </div>
        <div style="flex:1">
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Status</label>
            <select name="status" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
                <option value="active" {{ old('status',$banner->status)==='active'?'selected':'' }}>Active</option>
                <option value="inactive" {{ old('status',$banner->status)==='inactive'?'selected':'' }}>Inactive</option>
            </select>
        </div>
    </div>
</div>
<div style="display:flex;gap:8px;margin-top:12px">
    <a href="{{ route('website.banners.index') }}" style="height:36px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Update Banner</button>
</div>
</form>
</div>
@endsection
