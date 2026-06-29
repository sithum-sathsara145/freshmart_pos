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
    @foreach([['name','Product name','text'],['barcode','Barcode','text'],['description','Description','text']] as [$n,$l,$t])
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">{{ $l }}</label>
        <input type="{{ $t }}" name="{{ $n }}" value="{{ old($n,$product->$n) }}"
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    @endforeach
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">SKU (6 digits) — item code</label>
        <input type="text" name="sku" value="{{ old('sku',$product->sku) }}" maxlength="6" inputmode="numeric"
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
        @error('sku')<div style="color:#f87171;font-size:10px;margin-top:3px">{{ $message }}</div>@enderror
    </div>
    @php $units = ['Piece','Kg','Gram','Litre','Pack','Box','Dozen']; if(!in_array($product->unit, $units)) $units[] = $product->unit; @endphp
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Unit *</label>
        <select name="unit" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            @foreach($units as $u)
            <option value="{{ $u }}" {{ old('unit',$product->unit)==$u?'selected':'' }}>{{ $u }}</option>
            @endforeach
        </select>
    </div>
    <div style="margin-bottom:10px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:10px">
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:#e2e8f0;cursor:pointer">
            <input type="checkbox" name="is_weighed" value="1" {{ old('is_weighed', $product->is_weighed) ? 'checked' : '' }}
                onchange="document.getElementById('scale-plu-row').style.display=this.checked?'block':'none'"
                style="accent-color:#818cf8">
            Weighed item (sold by weight via scale)
        </label>
        <div id="scale-plu-row" style="margin-top:8px;{{ old('is_weighed', $product->is_weighed) ? '' : 'display:none' }}">
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Scale PLU — the item code programmed on the scale</label>
            <input type="text" name="scale_plu" value="{{ old('scale_plu', $product->scale_plu) }}" inputmode="numeric" placeholder="e.g. 12"
                style="width:160px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            @error('scale_plu')<div style="color:#f87171;font-size:10px;margin-top:3px">{{ $message }}</div>@enderror
        </div>
    </div>
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
        <input type="number" name="{{ $n }}" value="{{ old($n,$product->$n) }}" step="{{ $n === 'min_stock' ? '1' : '0.01' }}" min="0"
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    @endforeach
    <div><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Status</label>
        <select name="status" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="active" {{ $product->status==='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ $product->status==='inactive'?'selected':'' }}>Inactive</option>
        </select>
    </div>
    <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:#94a3b8;cursor:pointer;margin-top:12px">
        <input type="checkbox" name="show_in_online_store" value="1" {{ old('show_in_online_store', $product->show_in_online_store) ? 'checked' : '' }} style="accent-color:#818cf8">
        Show in online store
    </label>
</div>
</div>
<div>
@include('products._image_field', ['product' => $product])
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
