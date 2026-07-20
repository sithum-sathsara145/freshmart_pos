{{-- products/create.blade.php --}}
@extends('layouts.app')
@section('title','Add Product')
@section('page-title','Add New Product')

@section('content')
<div style="padding:14px 16px;max-width:800px">
<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
@csrf
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">

{{-- Left column --}}
<div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Basic information</div>

    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Product name *</label>
        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Anchor Milk 1L"
            style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Category</label>
            <select name="category_id" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
                <option value="">— Select —</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" {{ old('category_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Brand</label>
            <select name="brand_id" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
                <option value="">— Select —</option>
                @foreach($brands as $b)<option value="{{ $b->id }}" {{ old('brand_id')==$b->id?'selected':'' }}>{{ $b->name }}</option>@endforeach
            </select>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Barcode</label>
            <input type="text" name="barcode" value="{{ old('barcode') }}" placeholder="Scan or auto"
                style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">SKU (6 digits)</label>
            <input type="text" name="sku" value="{{ old('sku') }}" maxlength="6" inputmode="numeric" placeholder="Auto if blank"
                style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            @error('sku')<div style="color:var(--danger);font-size:10px;margin-top:3px">{{ $message }}</div>@enderror
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Unit *</label>
            <select name="unit" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
                @foreach(['Piece','Kg','Gram','Litre','Pack','Box','Dozen'] as $u)
                <option value="{{ $u }}" {{ old('unit')==$u?'selected':'' }}>{{ $u }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div style="margin-bottom:10px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:10px">
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text);cursor:pointer">
            <input type="checkbox" name="is_weighed" value="1" {{ old('is_weighed') ? 'checked' : '' }}
                onchange="document.getElementById('scale-plu-row').style.display=this.checked?'block':'none'"
                style="accent-color:var(--primary)">
            Weighed item (sold by weight via scale)
        </label>
        <div id="scale-plu-row" style="margin-top:8px;{{ old('is_weighed') ? '' : 'display:none' }}">
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Scale PLU — the item code programmed on the scale</label>
            <input type="text" name="scale_plu" value="{{ old('scale_plu') }}" inputmode="numeric" placeholder="e.g. 12"
                style="width:160px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
            @error('scale_plu')<div style="color:var(--danger);font-size:10px;margin-top:3px">{{ $message }}</div>@enderror
        </div>
    </div>

    <div>
        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Description</label>
        <textarea name="description" rows="3" placeholder="Short product description..."
            style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none;resize:none">{{ old('description') }}</textarea>
    </div>
</div>

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Pricing & stock</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Purchase price (Rs.) *</label>
            <input type="number" name="purchase_price" value="{{ old('purchase_price',0) }}" step="0.01" min="0" required
                style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Sale price (Rs.) *</label>
            <input type="number" name="sale_price" value="{{ old('sale_price',0) }}" step="0.01" min="0" required
                style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Tax %</label>
            <input type="number" name="tax_percent" value="{{ old('tax_percent',0) }}" step="0.01" min="0" max="100"
                style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Discount %</label>
            <input type="number" name="discount_percent" value="{{ old('discount_percent',0) }}" step="0.01" min="0" max="100"
                style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Opening stock</label>
            <input type="number" name="opening_stock" value="{{ old('opening_stock',0) }}" min="0"
                style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        </div>
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Min stock alert</label>
            <input type="number" name="min_stock" value="{{ old('min_stock',5) }}" min="0"
                style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Status</label>
            <select name="status" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <div style="display:flex;align-items:flex-end;padding-bottom:7px">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2);cursor:pointer">
                <input type="checkbox" name="show_in_online_store" value="1" {{ old('show_in_online_store') ? 'checked' : '' }} style="accent-color:var(--primary)">
                Show in online store
            </label>
        </div>
    </div>
</div>
</div>

{{-- Right column — image --}}
<div>
@include('products._image_field')

<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-top:12px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Variation (optional)</div>
    <select name="variation_type_id" style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 10px;outline:none">
        <option value="">No variation</option>
        @foreach($variationTypes as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach
    </select>
</div>
</div>
</div>

<div style="display:flex;gap:8px;margin-top:14px">
    <a href="{{ route('products.index') }}" style="height:36px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;text-decoration:none">Cancel</a>
    <button type="submit" style="height:36px;padding:0 20px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">
        <i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save Product
    </button>
</div>
</form>
</div>

@endsection
