<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Stock;
use App\Models\SaleItem;
use App\Models\VariationType;
use App\Models\Setting;
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

    // ── Bulk import from CSV / Excel ─────────────────────────────────────
    private const IMPORT_COLUMNS = [
        'name', 'sku', 'barcode', 'category', 'brand', 'unit', 'is_weighed', 'scale_plu',
        'purchase_price', 'sale_price', 'tax_percent', 'discount_percent', 'min_stock',
        'opening_stock', 'description', 'status', 'show_in_online_store', 'image_url',
    ];

    public function importForm()
    {
        return view('products.import', ['columns' => self::IMPORT_COLUMNS, 'result' => null]);
    }

    // Download a ready-to-fill sample (CSV or Excel) showing the expected columns.
    public function importSample(Request $request)
    {
        $format  = $request->get('format') === 'xlsx' ? 'xlsx' : 'csv';
        $samples = [
            // name, sku, barcode, category, brand, unit, is_weighed, scale_plu, purchase, sale, tax, disc, min, opening, description, status, online, image_url
            ['Coca-Cola 1.5L', '', '', 'Beverages', 'Coca-Cola', 'Piece', '0', '', '220', '280', '0', '0', '10', '48', 'Soft drink bottle', 'active', '1', ''],
            ['Sugar (loose)',   '', '', 'Groceries', '',          'Kg',    '1', '15', '230', '260', '0', '0', '5',  '100', 'Sold by weight on scale', 'active', '0', ''],
            ['Bakery Bun',      '', '', 'Bakery',    '',          'Piece', '0', '', '25',  '40',  '0', '0', '20', '0',   'Store-made — barcode auto', 'active', '0', ''],
            ['Old Item',        '', '', '',          '',          'Piece', '0', '', '0',   '0',   '0', '0', '0',  '0',   '', 'inactive', '0', ''],
        ];

        return $this->writeSpreadsheet(self::IMPORT_COLUMNS, $samples, $format, 'products_sample');
    }

    // Export products (honouring the same filters as the list) to CSV or Excel,
    // using the import column layout so the file can be edited and re-imported.
    public function export(Request $request)
    {
        $format   = $request->get('format') === 'xlsx' ? 'xlsx' : 'csv';
        $branchId = auth()->user()->branch_id;

        $products = Product::with(['category', 'brand'])
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('barcode', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            }))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->brand_id, fn ($q) => $q->where('brand_id', $request->brand_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->get();

        $rows = $products->map(fn ($p) => [
            $p->name, $p->sku, $p->barcode, $p->category?->name, $p->brand?->name, $p->unit,
            $p->is_weighed ? 1 : 0, $p->scale_plu,
            $p->purchase_price, $p->sale_price, $p->tax_percent, $p->discount_percent, $p->min_stock,
            $p->stockForBranch($branchId), $p->description, $p->status, $p->show_in_online_store ? 1 : 0,
            $p->imageUrl(),
        ])->all();

        return $this->writeSpreadsheet(self::IMPORT_COLUMNS, $rows, $format, 'products_export_' . date('Ymd_His'));
    }

    /** Write headers + rows to a CSV/XLSX file and return it as a download. */
    private function writeSpreadsheet(array $headers, iterable $rows, string $format, string $basename)
    {
        $writer = $format === 'xlsx'
            ? new \OpenSpout\Writer\XLSX\Writer()
            : new \OpenSpout\Writer\CSV\Writer();

        $tmp = tempnam(sys_get_temp_dir(), 'exp');
        $writer->openToFile($tmp);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($headers));
        foreach ($rows as $row) {
            $clean = array_map(fn ($v) => $v === null ? '' : $v, $row);
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($clean));
        }
        $writer->close();

        $mime = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv';

        return response()->download($tmp, $basename . '.' . $format, ['Content-Type' => $mime])
                         ->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|max:10240']);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt', 'xlsx'])) {
            return back()->with('error', 'Unsupported file. Please upload a .csv or .xlsx file.');
        }

        try {
            $rows = $this->readSpreadsheet($file->getRealPath(), $ext === 'xlsx' ? 'xlsx' : 'csv');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not read the file: ' . $e->getMessage());
        }

        $branchId    = auth()->user()->branch_id;
        $created     = 0;
        $skipped     = 0;
        $errors      = [];
        $seenSku     = [];
        $seenBarcode = [];
        $seenName    = [];

        foreach ($rows as $n => $r) {
            $line = $n + 2;                      // +1 header row, +1 to make it 1-based
            $name = trim((string) ($r['name'] ?? ''));
            if ($name === '') {
                $errors[] = "Row {$line}: missing product name — skipped.";
                continue;
            }

            $sku     = preg_replace('/\D/', '', (string) ($r['sku'] ?? ''));
            $barcode = trim((string) ($r['barcode'] ?? ''));
            $nameKey = mb_strtolower($name);

            // Ignore records that already exist, and duplicates within the file. Match on
            // SKU or barcode when given; otherwise fall back to the product name so a
            // name-only catalogue can be re-imported without creating duplicates.
            if ($sku !== '' && (isset($seenSku[$sku]) || Product::where('sku', $sku)->exists())) {
                $skipped++;
                continue;
            }
            if ($barcode !== '' && (isset($seenBarcode[$barcode]) || Product::where('barcode', $barcode)->exists())) {
                $skipped++;
                continue;
            }
            if ($sku === '' && $barcode === ''
                && (isset($seenName[$nameKey]) || Product::whereRaw('LOWER(name) = ?', [$nameKey])->exists())) {
                $skipped++;
                continue;
            }
            if ($sku !== '' && ! preg_match('/^\d{6}$/', $sku)) {
                $errors[] = "Row {$line}: SKU must be exactly 6 digits — skipped.";
                continue;
            }

            try {
                $isWeighed = $this->importBool($r['is_weighed'] ?? null);
                $scalePlu  = preg_replace('/\D/', '', (string) ($r['scale_plu'] ?? '')) ?: null;

                DB::beginTransaction();
                $product = Product::create([
                    'name'                 => $name,
                    'sku'                  => $sku ?: null,                 // model auto-generates when null
                    'barcode'              => $barcode ?: $this->generateBarcode(),
                    'category_id'          => $this->importLookup(Category::class, $r['category'] ?? null),
                    'brand_id'             => $this->importLookup(Brand::class, $r['brand'] ?? null),
                    'unit'                 => trim((string) ($r['unit'] ?? '')) ?: 'Piece',
                    'is_weighed'           => $isWeighed,
                    'scale_plu'            => $isWeighed ? $scalePlu : null,
                    'purchase_price'       => $this->importNum($r['purchase_price'] ?? 0),
                    'sale_price'           => $this->importNum($r['sale_price'] ?? 0),
                    'tax_percent'          => $this->importNum($r['tax_percent'] ?? 0),
                    'discount_percent'     => $this->importNum($r['discount_percent'] ?? 0),
                    'min_stock'            => (int) $this->importNum($r['min_stock'] ?? 0),
                    'description'          => trim((string) ($r['description'] ?? '')) ?: null,
                    'status'               => strtolower(trim((string) ($r['status'] ?? 'active'))) === 'inactive' ? 'inactive' : 'active',
                    'show_in_online_store' => $this->importBool($r['show_in_online_store'] ?? null),
                    'image'                => $this->importImageUrl($r['image_url'] ?? null),
                    'created_by'           => auth()->id(),
                ]);

                $opening = $this->importNum($r['opening_stock'] ?? 0);
                if ($opening > 0) {
                    Stock::create(['product_id' => $product->id, 'branch_id' => $branchId, 'quantity' => $opening]);
                }
                DB::commit();

                if ($sku !== '') {
                    $seenSku[$sku] = true;
                }
                $seenBarcode[$product->barcode] = true;
                $seenName[$nameKey] = true;
                $created++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors[] = "Row {$line}: " . $e->getMessage();
            }
        }

        return view('products.import', [
            'columns' => self::IMPORT_COLUMNS,
            'result'  => [
                'total'   => count($rows),
                'created' => $created,
                'skipped' => $skipped,
                'errors'  => $errors,
            ],
        ]);
    }

    /** Read the first sheet into an array of header-keyed rows. */
    private function readSpreadsheet(string $path, string $type): array
    {
        $reader = $type === 'xlsx'
            ? new \OpenSpout\Reader\XLSX\Reader()
            : new \OpenSpout\Reader\CSV\Reader();

        $reader->open($path);
        $rows    = [];
        $headers = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row->toArray());

                if ($headers === null) {
                    $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $cells);
                    continue;
                }
                if (count(array_filter($cells, fn ($v) => $v !== '' && $v !== null)) === 0) {
                    continue;   // blank line
                }

                $assoc = [];
                foreach ($headers as $i => $h) {
                    if ($h !== '') {
                        $assoc[$h] = $cells[$i] ?? null;
                    }
                }
                $rows[] = $assoc;
            }
            break;   // first sheet only
        }
        $reader->close();

        return $rows;
    }

    private function importLookup(string $model, ?string $name): ?int
    {
        $name = trim((string) $name);
        return $name === '' ? null : $model::firstOrCreate(['name' => $name])->id;
    }

    private function importBool($v): bool
    {
        return in_array(strtolower(trim((string) $v)), ['1', 'yes', 'y', 'true', 'active'], true);
    }

    private function importNum($v): float
    {
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $v);
    }

    private function importImageUrl($v): ?string
    {
        $v = trim((string) $v);
        return str_starts_with($v, 'http') ? $v : null;
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
            'is_weighed'      => 'boolean',
            'scale_plu'       => 'nullable|required_if:is_weighed,1|digits_between:1,20|unique:products,scale_plu',
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
        $validated['is_weighed'] = $request->boolean('is_weighed');
        if (! $validated['is_weighed']) {
            $validated['scale_plu'] = null;
        }
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
            'is_weighed'      => 'boolean',
            'scale_plu'       => 'nullable|required_if:is_weighed,1|digits_between:1,20|unique:products,scale_plu,' . $product->id,
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
        $validated['is_weighed'] = $request->boolean('is_weighed');
        if (! $validated['is_weighed']) {
            $validated['scale_plu'] = null;
        }
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

    // Generate an internal EAN-13 barcode for a store-made item that has no manufacturer
    // barcode. Uses the GS1 "in-store" range (prefix 20-29, default 21) so it can never
    // collide with real manufacturer barcodes, and carries a valid check digit so any
    // scanner reads it. Kept distinct from the scale prefix (default "2").
    private function generateBarcode(): string
    {
        $prefix = preg_replace('/\D/', '', (string) Setting::get('internal_barcode_prefix', '21'));
        if ($prefix === '' || strlen($prefix) > 6) {
            $prefix = '21';
        }
        $bodyLen = 12 - strlen($prefix);   // 12 data digits + 1 check digit = EAN-13

        do {
            $body = str_pad((string) random_int(0, (int) str_repeat('9', $bodyLen)), $bodyLen, '0', STR_PAD_LEFT);
            $data12 = $prefix . $body;
            $code   = $data12 . $this->ean13CheckDigit($data12);
        } while (Product::where('barcode', $code)->exists());

        return $code;
    }

    // Standard EAN-13 check digit for a 12-digit string.
    private function ean13CheckDigit(string $data12): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $data12[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        return (string) ((10 - ($sum % 10)) % 10);
    }
}
