{{-- products/index.blade.php --}}
@extends('layouts.app')
@section('title','Products')
@section('page-title','Product Manager')

@section('content')
@push('styles')<style>details > summary::-webkit-details-marker{display:none}</style>@endpush
<div style="padding:14px 16px" x-data="{ selected: [], allIds: @js($products->pluck('id')->map(fn($id) => (string) $id)->values()) }">

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    @foreach([['Total products',$stats['total'],'var(--text)'],['Active',$stats['active'],'var(--success)'],['Low stock',$stats['low_stock'],'var(--warning)'],['Out of stock',$stats['out'],'var(--danger)']] as [$lbl,$val,$col])
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:10px 12px">
        <div style="font-size:10px;color:var(--text-3);margin-bottom:4px">{{ $lbl }}</div>
        <div style="font-size:20px;font-weight:500;color:{{ $col }}">{{ $val }}</div>
    </div>
    @endforeach
</div>

{{-- Toolbar --}}
<div style="display:flex;gap:8px;margin-bottom:12px;align-items:center">
    <form method="GET" style="display:flex;gap:8px;flex:1">
        <div style="flex:1;display:flex;align-items:center;gap:7px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;padding:0 10px;height:34px">
            <i class="ti ti-search" style="font-size:13px;color:var(--text-3)"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Search name, barcode, SKU..." style="background:none;border:none;outline:none;color:var(--text);font-size:12px;width:100%">
        </div>
        <select name="category_id" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
            <option value="">All categories</option>
            @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
        </select>
        <select name="status" style="height:34px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;padding:0 8px;outline:none">
            <option value="">All status</option>
            <option value="active" {{ request('status')=='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ request('status')=='inactive'?'selected':'' }}>Inactive</option>
        </select>
        <button type="submit" style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Filter</button>
    </form>
    @php $exportFilters = request()->only('search','category_id','brand_id','status'); @endphp
    @can('products.export')
    <details style="position:relative">
        <summary style="height:34px;padding:0 12px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;cursor:pointer;list-style:none">
            <i class="ti ti-file-export" style="font-size:13px"></i>Export
        </summary>
        <div style="position:absolute;right:0;top:38px;background:var(--surface);border:.5px solid var(--border);border-radius:6px;z-index:20;min-width:150px;overflow:hidden;box-shadow:0 8px 22px var(--shadow)">
            <a href="{{ route('products.export', array_merge($exportFilters, ['format'=>'csv'])) }}" style="display:flex;align-items:center;gap:6px;padding:8px 12px;font-size:12px;color:var(--text-2);text-decoration:none">
                <i class="ti ti-file-text" style="font-size:13px"></i>Export as CSV</a>
            <a href="{{ route('products.export', array_merge($exportFilters, ['format'=>'xlsx'])) }}" style="display:flex;align-items:center;gap:6px;padding:8px 12px;font-size:12px;color:var(--text-2);text-decoration:none;border-top:.5px solid var(--border)">
                <i class="ti ti-file-spreadsheet" style="font-size:13px"></i>Export as Excel</a>
        </div>
    </details>
    @endcan
    @can('products.import')
    <a href="{{ route('products.import') }}" style="height:34px;padding:0 12px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-file-import" style="font-size:13px"></i>Import
    </a>
    @endcan
    @can('barcodes.print')
    <a href="{{ route('barcodes.labels') }}" style="height:34px;padding:0 12px;background:var(--surface-2);color:var(--text-2);border:.5px solid var(--border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-barcode" style="font-size:13px"></i>Print labels
    </a>
    @endcan
    @can('products.create')
    <a href="{{ route('products.create') }}" style="height:34px;padding:0 12px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>Add Product
    </a>
    @endcan
</div>

{{-- Bulk actions --}}
@can('products.delete')
<div x-show="selected.length" x-cloak style="display:flex;align-items:center;gap:10px;margin-bottom:10px;background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:8px 12px">
    <span style="font-size:12px;color:var(--text)"><span x-text="selected.length"></span> selected</span>
    <button type="button" @click="if (confirm('Delete ' + selected.length + ' selected product(s)? Products with sales history are skipped.')) $refs.bulkForm.submit()"
            style="height:30px;padding:0 12px;background:var(--danger-soft);border:.5px solid var(--danger-border-2);border-radius:6px;color:var(--danger-text);font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px">
        <i class="ti ti-trash" style="font-size:13px"></i> Delete selected
    </button>
    <button type="button" @click="selected = []" style="height:30px;padding:0 10px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Clear</button>
    <form x-ref="bulkForm" method="POST" action="{{ route('products.bulk-delete') }}" style="display:none">
        @csrf
        <template x-for="id in selected" :key="id"><input type="hidden" name="product_ids[]" :value="id"></template>
    </form>
</div>
@endcan

{{-- Table --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead>
        <tr style="border-bottom:.5px solid var(--border)">
            <th style="padding:9px 12px;text-align:center;width:34px"><input type="checkbox" @change="selected = $event.target.checked ? allIds.slice() : []" :checked="allIds.length && selected.length === allIds.length" title="Select all on this page" style="accent-color:var(--primary);width:15px;height:15px;cursor:pointer"></th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Product</th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Barcode</th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Category</th>
            <th style="padding:9px 12px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Brand</th>
            <th style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Buy price</th>
            <th style="padding:9px 12px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Sale price</th>
            <th style="padding:9px 12px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">Stock</th>
            <th style="padding:9px 12px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">Status</th>
            <th style="padding:9px 12px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $p)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:10px 12px;text-align:center"><input type="checkbox" value="{{ $p->id }}" x-model="selected" style="accent-color:var(--primary);width:15px;height:15px;cursor:pointer"></td>
            <td style="padding:10px 12px">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:34px;height:34px;background:var(--surface-2);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        @if($p->image)
                        <img src="{{ $p->imageUrl() }}" style="width:34px;height:34px;border-radius:6px;object-fit:cover">
                        @else
                        <i class="ti ti-package" style="color:var(--primary);font-size:16px"></i>
                        @endif
                    </div>
                    <div>
                        <div style="color:var(--text);font-weight:500">{{ $p->name }}</div>
                        <div style="color:var(--text-3);font-size:10px"><span style="color:var(--primary);font-family:monospace">SKU {{ $p->sku }}</span> · {{ $p->unit }}</div>
                    </div>
                </div>
            </td>
            <td style="padding:10px 12px;font-family:monospace;font-size:10px;color:var(--text-3)">{{ $p->barcode }}</td>
            <td style="padding:10px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:var(--info-soft);color:var(--info)">{{ $p->category?->name ?? '—' }}</span></td>
            <td style="padding:10px 12px;color:var(--text-2)">{{ $p->brand?->name ?? '—' }}</td>
            <td style="padding:10px 12px;text-align:right;color:var(--text-3)">Rs. {{ number_format($p->purchase_price) }}</td>
            <td style="padding:10px 12px;text-align:right;color:var(--primary-text);font-weight:500">Rs. {{ number_format($p->sale_price) }}</td>
            <td style="padding:10px 12px;text-align:center;color:{{ $p->current_stock <= 0 ? 'var(--danger)' : ($p->current_stock < $p->min_stock ? 'var(--warning)' : 'var(--text)') }};font-weight:500">
                {{ $p->current_stock }}
            </td>
            <td style="padding:10px 12px;text-align:center">
                <span style="font-size:10px;padding:2px 8px;border-radius:10px;font-weight:500;background:{{ $p->status==='active' ? 'var(--success-soft)' : 'var(--surface-2)' }};color:{{ $p->status==='active' ? 'var(--success)' : 'var(--text-2)' }}">
                    {{ ucfirst($p->status) }}
                </span>
            </td>
            <td style="padding:10px 12px;text-align:center">
                <div style="display:flex;gap:4px;justify-content:center">
                    @can('products.edit')
                    <a href="{{ route('products.edit',$p) }}" style="width:27px;height:27px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none" title="Edit"><i class="ti ti-edit" style="font-size:12px"></i></a>
                    @endcan
                    @can('barcodes.print')
                    <a href="{{ route('products.barcode',$p) }}" style="width:27px;height:27px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--info);text-decoration:none" title="Print barcode"><i class="ti ti-barcode" style="font-size:12px"></i></a>
                    @endcan
                    <a href="{{ route('products.show',$p) }}" style="width:27px;height:27px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--text-2);text-decoration:none" title="View"><i class="ti ti-eye" style="font-size:12px"></i></a>
                    @can('products.delete')
                    <form method="POST" action="{{ route('products.destroy',$p) }}" onsubmit="return confirm('Delete this product?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="width:27px;height:27px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;color:var(--danger);cursor:pointer" title="Delete"><i class="ti ti-trash" style="font-size:12px"></i></button>
                    </form>
                    @endcan
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="10" style="padding:32px;text-align:center;color:var(--text-4)">
            <i class="ti ti-package" style="font-size:28px;display:block;margin-bottom:8px"></i>No products found
        </td></tr>
        @endforelse
    </tbody>
</table>
</div>

{{-- Pagination --}}
<div style="margin-top:12px">{{ $products->links() }}</div>
</div>
@endsection
