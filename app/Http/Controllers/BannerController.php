<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->paginate(20);
        return view('banners.index', compact('banners'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Banner::create($data);
        return redirect()->route('website.banners.index')->with('success', 'Banner added.');
    }

    public function edit(Banner $banner)
    {
        return view('banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $banner->update($this->validated($request));
        return redirect()->route('website.banners.index')->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();
        return back()->with('success', 'Banner deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'      => 'nullable|string|max:200',
            'image'      => 'required|string|max:255',
            'link'       => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'status'     => 'required|in:active,inactive',
        ]);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        return $data;
    }
}
