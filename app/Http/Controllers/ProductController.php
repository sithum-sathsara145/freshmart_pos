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

    // Signed params for direct browser → Cloudinary uploads (keeps the secret server-side).
    public function uploadSignature()
    {
        try {
            return response()->json(app(CloudinaryService::class)->signedUploadParams());
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Validation rules for the image upload. A browser-supplied URL must point at
     * our own Cloudinary cloud, so a client can't store an arbitrary external image.
     * max:255 matches the products.image column width.
     */
    private function imageRules(): array
    {
        $prefix = 'https://res.cloudinary.com/' . config('services.cloudinary.cloud_name') . '/';

        return [
            'image'           => 'nullable|image|max:5120',
            'image_url'       => 'nullable|string|max:255|starts_with:' . $prefix,
            'image_public_id' => 'nullable|string|max:255',
        ];
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
            'show_in_online_store' => 'boolean',
        ] + $this->imageRules());
        $validated['show_in_online_store'] = $request->boolean('show_in_online_store');
        // image / image_public_id are set explicitly below from whichever upload path ran.
        unset($validated['image_url'], $validated['image_public_id']);

        $uploadedPublicId = null;   // track for cleanup if the DB write fails
        DB::beginTransaction();
        try {
            if ($request->filled('image_url')) {
                // Browser already uploaded straight to Cloudinary — just store the result.
                $validated['image']           = $request->input('image_url');
                $validated['image_public_id'] = $request->input('image_public_id');
                $uploadedPublicId             = $request->input('image_public_id');
            } elseif ($request->hasFile('image')) {
                // Fallback: direct upload didn't run, so upload from the server.
                try {
                    $up = app(CloudinaryService::class)->upload($request->file('image')->getRealPath());
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $msg = $e->getMessage();   // already user-friendly from the service
                    if ($request->wantsJson()) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return back()->with('error', $msg)->withInput();
                }
                $validated['image']           = $up['url'];
                $validated['image_public_id'] = $up['public_id'];
                $uploadedPublicId             = $up['public_id'];
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
            $msg = "Product '{$product->name}' added successfully.";
            if ($request->wantsJson()) {
                session()->flash('success', $msg);
                return response()->json(['redirect' => route('products.index')]);
            }
            return redirect()->route('products.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            // Don't leave an orphaned image on Cloudinary when the product wasn't saved.
            if ($uploadedPublicId) {
                app(CloudinaryService::class)->delete($uploadedPublicId);
            }
            $msg = 'Failed to save product: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
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
            'show_in_online_store' => 'boolean',
        ] + $this->imageRules());
        $validated['show_in_online_store'] = $request->boolean('show_in_online_store');
        unset($validated['image_url'], $validated['image_public_id']);

        if ($request->filled('image_url')) {
            // Browser uploaded a new image straight to Cloudinary.
            $newPublicId = $request->input('image_public_id');
            if ($product->image_public_id && $product->image_public_id !== $newPublicId) {
                app(CloudinaryService::class)->delete($product->image_public_id);   // drop the old
            }
            $validated['image']           = $request->input('image_url');
            $validated['image_public_id'] = $newPublicId;
        } elseif ($request->hasFile('image')) {
            try {
                $cloud = app(CloudinaryService::class);
                $up = $cloud->upload($request->file('image')->getRealPath());   // upload new first
                $cloud->delete($product->image_public_id);                      // then drop the old
                $validated['image']           = $up['url'];
                $validated['image_public_id'] = $up['public_id'];
            } catch (\Throwable $e) {
                $msg = 'Image upload failed: ' . $e->getMessage();
                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->with('error', $msg)->withInput();
            }
        } elseif ($request->boolean('remove_image')) {
            // User cleared the image without picking a new one.
            $cloud = app(CloudinaryService::class);
            if ($product->image_public_id) {
                $cloud->delete($product->image_public_id);
            } elseif ($product->image && ! str_starts_with($product->image, 'http')) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image']           = null;
            $validated['image_public_id'] = null;
        }

        $product->update($validated);

        $msg = "Product '{$product->name}' updated.";
        if ($request->wantsJson()) {
            session()->flash('success', $msg);
            return response()->json(['redirect' => route('products.index')]);
        }
        return redirect()->route('products.index')->with('success', $msg);
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
