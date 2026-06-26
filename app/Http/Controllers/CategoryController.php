<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%"))
            ->with('parent')
            ->withCount('products')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $parents = Category::orderBy('name')->get();

        return view('categories.index', compact('categories', 'parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:150',
            'parent_id' => 'nullable|exists:categories,id',
        ]);
        Category::create($request->only(['name', 'parent_id', 'description']));
        return redirect()->route('categories.index')->with('success', 'Category added.');
    }

    public function edit(Category $category)
    {
        $parents = Category::where('id', '!=', $category->id)->orderBy('name')->get();
        return view('categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'      => 'required|string|max:150',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $data = $request->only(['name', 'parent_id', 'description']);
        // A category cannot be its own parent.
        if (($data['parent_id'] ?? null) == $category->id) {
            $data['parent_id'] = null;
        }
        $category->update($data);
        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete — category has products.');
        }
        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete — category has sub-categories.');
        }
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }
}
