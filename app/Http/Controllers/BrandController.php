<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%"))
            ->withCount('products')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:150|unique:brands,name']);
        Brand::create($request->only(['name', 'description']));
        return redirect()->route('brands.index')->with('success', 'Brand added.');
    }

    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate(['name' => 'required|string|max:150|unique:brands,name,' . $brand->id]);
        $brand->update($request->only(['name', 'description']));
        return redirect()->route('brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return back()->with('error', 'Cannot delete — brand has products.');
        }
        $brand->delete();
        return back()->with('success', 'Brand deleted.');
    }
}
