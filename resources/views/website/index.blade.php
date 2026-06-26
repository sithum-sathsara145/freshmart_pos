{{-- website/index.blade.php --}}
@extends('layouts.app')
@section('title','Website Setup')
@section('page-title','Website Setup')
@section('content')
<div style="padding:14px 16px;display:grid;grid-template-columns:180px 1fr;gap:14px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 0;align-self:start">
    @foreach([['cards','ti-cards','Product cards'],['front','ti-home','Front settings'],['banner','ti-photo','Banners'],['seo','ti-search','SEO']] as [$k,$i,$l])
    <div onclick="wSwitch('{{ $k }}')" id="wnav-{{ $k }}" style="display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:12px;color:#94a3b8;cursor:pointer;border-left:2px solid transparent">
        <i class="ti {{ $i }}" style="font-size:14px"></i>{{ $l }}
    </div>
    @endforeach
</div>
<div>
<div id="wsec-cards">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Product cards — online store</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px">
    @foreach($products->take(3) as $p)
    <div style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
        <div style="height:80px;background:#1e2130;display:flex;align-items:center;justify-content:center"><i class="ti ti-package" style="color:#818cf8;font-size:28px"></i></div>
        <div style="padding:8px 10px"><div style="font-size:11px;font-weight:500;color:#e2e8f0">{{ $p->name }}</div><div style="font-size:12px;color:#4ade80;margin-top:2px">Rs. {{ number_format($p->sale_price) }}</div></div>
    </div>
    @endforeach
    </div>
    <form method="POST" action="{{ route('website.settings') }}">
    @csrf
    @foreach([['show_stock_status','Show stock status'],['show_discount_badge','Show discount badge'],['enable_ordering','Enable online ordering'],['show_categories','Show categories menu']] as [$k,$l])
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <span style="color:#e2e8f0">{{ $l }}</span>
        <input type="checkbox" name="{{ $k }}" value="1" {{ ($settings[$k]??'1')==='1'?'checked':'' }} style="accent-color:#818cf8;width:16px;height:16px">
    </div>
    @endforeach
    <button type="submit" style="margin-top:12px;height:34px;padding:0 16px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:12px;margin-right:4px"></i>Save settings</button>
    </form>
</div>
</div>
<div id="wsec-front" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Front page settings</div>
    <form method="POST" action="{{ route('website.settings') }}">
    @csrf
    @foreach([['store_name','Store name (online)','text'],['tagline','Tagline','text'],['announcement','Announcement bar','text']] as [$k,$l,$t])
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">{{ $l }}</label>
        <input type="{{ $t }}" name="{{ $k }}" value="{{ $settings[$k]??'' }}" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    @endforeach
    <button type="submit" style="height:34px;padding:0 16px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:12px;margin-right:4px"></i>Save</button>
    </form>
</div>
</div>
<div id="wsec-banner" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Banners</div>
    @forelse($banners as $b)
    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <div style="width:60px;height:32px;background:#1e2130;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#64748b">Banner</div>
        <div style="flex:1;color:#e2e8f0">{{ $b->title }}</div>
        <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#14532d;color:#4ade80">Active</span>
    </div>
    @empty
    <div style="color:#4a5568;font-size:12px">No banners added</div>
    @endforelse
</div>
</div>
<div id="wsec-seo" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">SEO settings</div>
    <form method="POST" action="{{ route('website.settings') }}">
    @csrf
    @foreach([['seo_title','Page title','text'],['seo_description','Meta description','text'],['google_analytics_id','Google Analytics ID','text'],['facebook_pixel_id','Facebook Pixel ID','text']] as [$k,$l,$t])
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">{{ $l }}</label>
        <input type="{{ $t }}" name="{{ $k }}" value="{{ $settings[$k]??'' }}" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    @endforeach
    <button type="submit" style="height:34px;padding:0 16px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-check" style="font-size:12px;margin-right:4px"></i>Save SEO</button>
    </form>
</div>
</div>
</div>
</div>
@push('scripts')
<script>
function wSwitch(k) {
    ['cards','front','banner','seo'].forEach(s=>{document.getElementById('wsec-'+s).style.display=s===k?'block':'none'});
    document.querySelectorAll('[id^="wnav-"]').forEach(n=>{n.style.background='';n.style.color='#94a3b8';n.style.borderLeft='2px solid transparent'});
    const nav=document.getElementById('wnav-'+k);
    nav.style.background='#1e2130';nav.style.color='#a5b4fc';nav.style.borderLeft='2px solid #818cf8';
}
wSwitch('cards');
</script>
@endpush
@endsection
