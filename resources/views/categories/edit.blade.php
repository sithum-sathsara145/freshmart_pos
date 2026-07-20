{{-- categories/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Category')
@section('page-title','Edit Category')
@section('content')
<div style="padding:14px 16px;max-width:500px">
<form method="POST" action="{{ route('categories.update',$category) }}">
@csrf @method('PUT')
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:16px">
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Category name *</label>
        <input type="text" name="name" value="{{ old('name',$category->name) }}" required style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        @error('name')<div style="color:var(--danger);font-size:11px;margin-top:4px">{{ $message }}</div>@enderror
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Parent category</label>
        <select name="parent_id" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            <option value="">— None (top level) —</option>
            @foreach($parents as $p)
            <option value="{{ $p->id }}" {{ old('parent_id',$category->parent_id)==$p->id?'selected':'' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Description</label>
        <textarea name="description" rows="3" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none;resize:vertical">{{ old('description',$category->description) }}</textarea>
    </div>
</div>
<div style="display:flex;gap:8px;margin-top:12px">
    <a href="{{ route('categories.index') }}" style="height:36px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Update Category</button>
</div>
</form>
</div>
@endsection
