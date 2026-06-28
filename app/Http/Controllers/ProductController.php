<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Stock;
use App\Models\SaleItem;
use App\Models\VariationType;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $products = Product::with(['category', 'brand'])
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('barcode', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            }))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->brand_id, fn($q) => $q->where('brand_id', $request->brand_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // Attach current stock for each product
        $products->each(function ($p) use ($branchId) {
            $p->current_stock = $p->stockForBranch($branchId);
        });

        $categories = Category::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();

        $stats = [
            'total'     => Product::count(),
            'active'    => Product::where('status', 'active')->count(),
            'low_stock' => Product::whereHas('stocks', fn($q) => $q->where('branch_id', $branchId)->whereRaw('quantity < products.min_stock')->where('quantity', '>', 0))->count(),
            'out'       => Product::whereHas('stocks', fn($q) => $q->where('branch_id', $branchId)->where('quantity', '<=', 0))->count(),
        ];

        return view('products.index', compact('products', 'categories', 'brands', 'stats'));
    }

    public function create()
    {
        $categories     = Category::orderBy('name')->get();
        $brands         = Brand::orderBy('name')->get();
        $variationTypes = VariationType::with('values')->get();

        return view('products.create', compact('categories', 'brands', 'variationTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'barcode'         => 'nullable|string|unique:products,barcode',
            'sku'             => 'nullable|digits:6|unique:products,sku',
            'category_id'     => 'nullable|exists:categories,id',
            'brand_id'        => 'nullable|exists:brands,id',
            'unit'            => 'required|string|max:50',
            'purchase_price'  => 'required|numeric|min:0',
            'sale_price'      => 'required|numeric|min:0',
            'tax_percent'     => 'nullable|numeric|min:0|max:100',
            'discount_percent'=> 'nullable|numeric|min:0|max:100',
            'min_stock'       => 'nullable|integer|min:0',
            'opening_stock'   => 'nullable|numeric|min:0',
            'description'     => 'nullable|string',
            'status'          => 'required|in:active,inactive',
            'image'           => 'nullable|image|max:2048',
            'show_in_online_store' => 'boolean',
        ]);
        $validated['show_in_online_store'] = $request->boolean('show_in_online_store');

        DB::beginTransaction();
        try {
            // Upload image to Cloudinary
            if ($request->hasFile('image')) {
                $up = app(CloudinaryService::class)->upload($request->file('image')->getRealPath());
                $validated['image']           = $up['url'];
                $validated['image_public_id'] = $up['public_id'];
            }

            // Auto-generate barcode if not provided
            if (empty($validated['barcode'])) {
                $validated['barcode'] = $this->generateBarcode();
            }

            $validated['created_by'] = auth()->id();
            $product = Product::create($validated);

            // Set opening stock for each branch
            $openingStock = $request->opening_stock ?? 0;
            if ($openingStock > 0) {
                Stock::create([
                    'product_id' => $product->id,
                    'branch_id'  => auth()->user()->branch_id,
                    'quantity'   => $openingStock,
                ]);
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', "Product '{$product->name}' added successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save product: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Product $product)
    {
        $branchId = auth()->user()->branch_id;
        $product->load(['category', 'brand']);
        $product->current_stock = $product->stockForBranch($branchId);

        // Sales history
        $salesHistory = SaleItem::where('product_id', $product->id)
            ->with('sale')
            ->latest()
            ->limit(10)
            ->get();

        return view('products.show', compact('product', 'salesHistory'));
    }

    public function edit(Product $product)
    {
        $categories     = Category::orderBy('name')->get();
        $brands         = Brand::orderBy('name')->get();
        $variationTypes = VariationType::with('values')->get();

        return view('products.edit', compact('product', 'categories', 'brands', 'variationTypes'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'barcode'         => 'nullable|string|unique:products,barcode,' . $product->id,
            'sku'             => 'nullable|digits:6|unique:products,sku,' . $product->id,
            'category_id'     => 'nullable|exists:categories,id',
            'brand_id'        => 'nullable|exists:brands,id',
            'unit'            => 'required|string|max:50',
            'purchase_price'  => 'required|numeric|min:0',
            'sale_price'      => 'required|numeric|min:0',
            'tax_percent'     => 'nullable|numeric|min:0|max:100',
            'discount_percent'=> 'nullable|numeric|min:0|max:100',
            'min_stock'       => 'nullable|integer|min:0',
            'description'     => 'nullable|string',
            'status'          => 'required|in:active,inactive',
            'image'           => 'nullable|image|max:2048',
            'show_in_online_store' => 'boolean',
        ]);
        $validated['show_in_online_store'] = $request->boolean('show_in_online_store');

        if ($request->hasFile('image')) {
            try {
                $cloud = app(CloudinaryService::class);
                $cloud->delete($product->image_public_id);   // remove the old Cloudinary image
                $up = $cloud->upload($request->file('image')->getRealPath());
                $validated['image']           = $up['url'];
                $validated['image_public_id'] = $up['public_id'];
            } catch (\Throwable $e) {
                return back()->with('error', 'Image upload failed: ' . $e->getMessage())->withInput();
            }
        }

        $product->update($validated);

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' updated.");
    }

    public function destroy(Product $product)
    {
        // Check if used in any sale
        if ($product->saleItems()->exists()) {
            return back()->with('error', 'Cannot delete — product has sales history.');
        }

        $cloud = app(CloudinaryService::class);
        if ($product->image_public_id) {
            $cloud->delete($product->image_public_id);
        } elseif ($product->image && ! str_starts_with($product->image, 'http')) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    // AJAX search for POS and other screens
    public function apiSearch(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $q        = $request->get('q', '');
        $category = $request->get('category');

        return response()->json(
            Product::with(['category'])
                ->where('status', 'active')
                ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%$q%")
                          ->orWhere('barcode', 'like', "%$q%")
                          ->orWhere('sku', 'like', "%$q%");
                }))
                ->when($category, fn($query) => $query->where('category_id', $category))
                ->limit(30)
                ->get()
                ->map(fn($p) => [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'sku'         => $p->sku,
                    'barcode'     => $p->barcode,
                    'price'       => (float) $p->sale_price,
                    'purchase_price' => (float) $p->purchase_price,
                    'tax_percent' => (float) $p->tax_percent,
                    'unit'        => $p->unit,
                    'stock'       => $p->stockForBranch($branchId),
                    'image'       => $p->imageUrl(),
                    'category'    => $p->category?->name,
                ])
        );
    }

    public function apiShow(Product $product)
    {
        $branchId = auth()->user()->branch_id;
        return response()->json([
            'id'          => $product->id,
            'name'        => $product->name,
            'sku'         => $product->sku,
            'barcode'     => $product->barcode,
            'price'       => (float) $product->sale_price,
            'tax_percent' => (float) $product->tax_percent,
            'unit'        => $product->unit,
            'stock'       => $product->stockForBranch($branchId),
        ]);
    }

    private function generateBarcode(): string
    {
        do {
            $code = (string) random_int(1000000000000, 9999999999999);
        } while (Product::where('barcode', $code)->exists());

        return $code;
    }
}
