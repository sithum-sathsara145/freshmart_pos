{{-- products/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Product')
@section('page-title','Edit Product')
@section('content')
<div style="padding:14px 16px;max-width:800px">
<form method="POST" action="{{ route('products.update',$product) }}" enctype="multipart/form-data">
@csrf @method('PUT')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
<div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Basic information</div>
    @foreach([['name','Product name','text'],['barcode','Barcode / SKU','text'],['description','Description','text']] as [$n,$l,$t])
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">{{ $l }}</label>
        <input type="{{ $t }}" name="{{ $n }}" value="{{ old($n,$product->$n) }}"
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    @endforeach
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <div><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Category</label>
            <select name="category_id" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
                <option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ $product->category_id==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Brand</label>
            <select name="brand_id" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
                <option value="">—</option>@foreach($brands as $b)<option value="{{ $b->id }}" {{ $product->brand_id==$b->id?'selected':'' }}>{{ $b->name }}</option>@endforeach
            </select>
        </div>
    </div>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Pricing</div>
    @foreach([['purchase_price','Purchase price'],['sale_price','Sale price'],['tax_percent','Tax %'],['discount_percent','Discount %'],['min_stock','Min stock alert']] as [$n,$l])
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">{{ $l }}</label>
        <input type="number" name="{{ $n }}" value="{{ old($n,$product->$n) }}" step="0.01" min="0"
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    @endforeach
    <div><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Status</label>
        <select name="status" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="active" {{ $product->status==='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ $product->status==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>
</div>
</div>
<div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Product image</div>
    @if($product->image)
    <img src="{{ asset('storage/'.$product->image) }}" style="width:100%;height:140px;object-fit:cover;border-radius:8px;margin-bottom:8px">
    @endif
    <label style="width:100%;height:{{ $product->image?'50px':'140px' }};background:#0f1117;border:.5px dashed #2a2d3a;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748b;font-size:12px" for="img-input">
        <i class="ti ti-upload" style="font-size:16px;margin-right:6px"></i>Change image
    </label>
    <input type="file" name="image" id="img-input" accept="image/*" style="display:none">
</div>
</div>
</div>
<div style="display:flex;gap:8px;margin-top:14px">
    <a href="{{ route('products.index') }}" style="height:36px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <a href="{{ route('products.barcode',$product) }}" style="height:36px;padding:0 14px;background:#1e3a5f;color:#60a5fa;border:.5px solid #1e3a5f;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-barcode" style="font-size:13px"></i>Print barcode</a>
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Update Product</button>
</div>
</form>
</div>
@endsection
