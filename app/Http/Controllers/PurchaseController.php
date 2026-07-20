<?php

namespace App\Http\Controllers;

use App\Support\CurrentBranch;
use App\Support\DocumentNumber;
use App\Support\Ledger;
use App\Support\Spreadsheet;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Category;
use App\Models\Stock;
use App\Models\StockLayer;
use App\Models\Payment;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $branchId = CurrentBranch::id();

        $purchases = Purchase::with(['supplier', 'user'])
            ->whereBranch($branchId)
            ->when($request->search, fn($q) => $q->where('bill_no', 'like', "%{$request->search}%")
                ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$request->search}%")))
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->from_date, fn($q) => $q->whereDate('purchase_date', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('purchase_date', '<=', $request->to_date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'month_total'    => Purchase::whereBranch($branchId)->whereMonth('created_at', now()->month)->sum('total'),
            'month_count'    => Purchase::whereBranch($branchId)->whereMonth('created_at', now()->month)->count(),
            'balance_due'    => Purchase::whereBranch($branchId)->where('payment_status', '!=', 'paid')->sum('balance_due'),
            'paid_this_month'=> Purchase::whereBranch($branchId)->whereMonth('created_at', now()->month)->sum('paid_amount'),
        ];

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.index', compact('purchases', 'stats', 'suppliers'));
    }

    public function create()
    {
        $branchId   = CurrentBranch::id();
        $suppliers  = Supplier::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $accounts   = Account::whereBranch($branchId)->get();
        return view('purchases.create', compact('suppliers', 'categories', 'accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'        => 'required|exists:suppliers,id',
            'purchase_date'      => 'required|date',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.name'       => 'nullable|string|max:255',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_no'   => 'nullable|string|max:50',
            'items.*.mrp'        => 'nullable|numeric|min:0',
            'items.*.sale_price' => 'nullable|numeric|min:0',
        ]);

        if (! $branchId = CurrentBranch::requireId()) {
            return back()->withInput()->with('error', CurrentBranch::pickBranchMessage());
        }

        DB::beginTransaction();
        try {
            $subtotal = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $discount = (float) ($request->discount_amount ?? 0);
            $tax      = (float) ($request->tax_amount ?? 0);
            $total    = $subtotal - $discount + $tax;
            $paid     = (float) ($request->paid_amount ?? 0);

            $purchase = Purchase::create([
                'bill_no'         => DocumentNumber::next('purchase'),
                'supplier_id'     => $request->supplier_id,
                'branch_id'       => $branchId,
                'user_id'         => auth()->id(),
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => $tax,
                'total'           => $total,
                'paid_amount'     => $paid,
                'balance_due'     => max(0, $total - $paid),
                'payment_status'  => $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                'purchase_date'   => $request->purchase_date,
                'due_date'        => $request->due_date,
                'notes'           => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $this->createPurchaseItem($purchase, $item, $branchId, $request->purchase_date);
            }

            // Update supplier balance
            Supplier::find($request->supplier_id)->increment('total_purchases', $total);
            if ($paid < $total) {
                Supplier::find($request->supplier_id)->increment('balance_due', $total - $paid);
            }

            // Payment out — honour the chosen account, map the method to a valid ENUM.
            if ($paid > 0) {
                $account = ($request->account_id ? Account::whereBranch($branchId)->find($request->account_id) : null)
                        ?? Account::whereBranch($branchId)->first();
                if ($account) {
                    $methodMap = ['cash' => 'cash', 'bank' => 'bank', 'cheque' => 'cheque', 'card' => 'card', 'credit' => 'cash'];
                    Payment::create([
                        'reference_no' => 'PAY-' . strtoupper(Str::random(8)),
                        'type'         => 'payment_out',
                        'account_id'   => $account->id,
                        'party_type'   => 'supplier',
                        'party_id'     => $request->supplier_id,
                        'purchase_id'  => $purchase->id,
                        'amount'       => $paid,
                        'method'       => $methodMap[$request->payment_method] ?? 'cash',
                        'created_by'   => auth()->id(),
                    ]);
                    Ledger::debit($account, $paid, [
                        'description' => "Purchase {$purchase->bill_no}",
                        'source_type' => 'purchase',
                        'source_id'   => $purchase->id,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('purchases.index')->with('success', "Purchase #{$purchase->bill_no} saved.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Purchase failed: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Purchase $purchase)
    {
        CurrentBranch::guard($purchase->branch_id);
        $purchase->load(['items.product', 'supplier', 'branch', 'user']);
        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        CurrentBranch::guard($purchase->branch_id);
        if ($reason = $this->blockingReason($purchase)) {
            return redirect()->route('purchases.show', $purchase)->with('error', $reason);
        }
        $suppliers = Supplier::orderBy('name')->get();
        $purchase->load(['items.product']);
        return view('purchases.edit', compact('purchase', 'suppliers'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        CurrentBranch::guard($purchase->branch_id);
        if ($reason = $this->blockingReason($purchase)) {
            return back()->with('error', $reason);
        }

        $request->validate([
            'purchase_date'      => 'required|date',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.name'       => 'nullable|string|max:255',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_no'   => 'nullable|string|max:50',
            'items.*.mrp'        => 'nullable|numeric|min:0',
            'items.*.sale_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $branchId  = $purchase->branch_id;
            $purchase->load('items.layer', 'items.product');

            // Weighed products already priced into the running WAC can't be safely re-blended,
            // so the edit form locks their qty/cost — enforce that server-side too, keyed by
            // product so a tampered request can't sneak a changed qty/cost through.
            $lockedWeighed = $purchase->items
                ->filter(fn($i) => $i->product && $i->product->is_weighed)
                ->keyBy('product_id');

            // Reverse this purchase's own layers/aggregate contribution (guard above already
            // confirmed none of it has been consumed) before recreating from the submitted items.
            foreach ($purchase->items as $item) {
                if ($item->layer) {
                    Stock::where('product_id', $item->product_id)->whereBranch($branchId)
                        ->decrement('quantity', (float) $item->layer->qty_remaining);
                    $item->layer->delete();
                }
            }
            $purchase->items()->delete();

            $subtotal = 0.0;
            foreach ($request->items as $item) {
                if ($locked = $lockedWeighed->get($item['product_id'])) {
                    $item['quantity']   = (float) $locked->quantity;
                    $item['unit_price'] = (float) $locked->unit_price;
                }
                $purchaseItem = $this->createPurchaseItem($purchase, $item, $branchId, $request->purchase_date);
                $subtotal += (float) $purchaseItem->subtotal;
            }

            $oldTotal = (float) $purchase->total;
            $discount = (float) ($request->discount_amount ?? 0);
            $tax      = (float) ($request->tax_amount ?? 0);
            $total    = $subtotal - $discount + $tax;
            $paid     = (float) $purchase->paid_amount;   // unchanged here — use the "Pay" flow for that

            $purchase->update([
                'purchase_date'   => $request->purchase_date,
                'due_date'        => $request->due_date,
                'notes'           => $request->notes,
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => $tax,
                'total'           => $total,
                'balance_due'     => max(0, $total - $paid),
                'payment_status'  => $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            ]);

            // Supplier stats moved with the total delta; paid/payments are untouched here.
            $supplier = Supplier::find($purchase->supplier_id);
            if ($supplier) {
                $delta = $total - $oldTotal;
                if ($delta > 0) {
                    $supplier->increment('total_purchases', $delta);
                    $supplier->increment('balance_due', $delta);
                } elseif ($delta < 0) {
                    $supplier->decrement('total_purchases', -$delta);
                    $dec = min($supplier->balance_due, -$delta);
                    if ($dec > 0) $supplier->decrement('balance_due', $dec);
                }
            }

            DB::commit();
            return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Update failed: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Purchase $purchase)
    {
        CurrentBranch::guard($purchase->branch_id);
        if ($purchase->payment_status === 'paid') {
            return back()->with('error', 'Cannot delete a fully paid purchase.');
        }
        if ($reason = $this->blockingReason($purchase)) {
            return back()->with('error', $reason);
        }

        DB::beginTransaction();
        try {
            $branchId = $purchase->branch_id;
            $purchase->load('items.layer', 'payments');

            foreach ($purchase->items as $item) {
                if ($item->layer) {
                    Stock::where('product_id', $item->product_id)->whereBranch($branchId)
                        ->decrement('quantity', (float) $item->layer->qty_remaining);
                    $item->layer->delete();
                }
            }

            foreach ($purchase->payments as $payment) {
                Ledger::credit($payment->account_id, (float) $payment->amount, [
                    'description' => "Reversed — purchase {$purchase->bill_no} deleted",
                    'source_type' => 'purchase',
                    'source_id'   => $purchase->id,
                ]);
                $payment->delete();
            }

            $supplier = Supplier::find($purchase->supplier_id);
            if ($supplier) {
                $supplier->decrement('total_purchases', $purchase->total);
                $due = min($supplier->balance_due, max(0, $purchase->total - $purchase->paid_amount));
                if ($due > 0) $supplier->decrement('balance_due', $due);
            }

            $purchase->items()->delete();
            $purchase->delete();

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase deleted and stock reversed.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    public function bill(int $id)
    {
        $purchase = Purchase::with(['items.product', 'supplier', 'branch'])
            ->whereBranch(CurrentBranch::id())->findOrFail($id);
        $settings = \App\Models\Setting::pluck('value', 'key_name');
        $pdf      = Pdf::loadView('purchases.bill_pdf', compact('purchase', 'settings'))->setPaper('A4');
        return $pdf->download("Bill-{$purchase->bill_no}.pdf");
    }

    // ── Goods-received import ────────────────────────────────────────────────
    //
    // Takes a supplier's invoice as a spreadsheet and turns it into a real
    // purchase, so received stock arrives at its actual cost with the supplier
    // and the amount owed recorded — unlike the product importer, which only
    // trues up quantities.

    private const IMPORT_COLUMNS = ['barcode', 'sku', 'name', 'quantity', 'unit_price', 'batch_no', 'mrp', 'sale_price'];

    public function importForm()
    {
        return view('purchases.import', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'result'    => null,
        ]);
    }

    public function importSample(Request $request)
    {
        $format  = $request->get('format') === 'xlsx' ? 'xlsx' : 'csv';
        $samples = [
            ['4791234567890', '',       '',                 '24', '220', 'B-2291', '300', '280'],
            ['',              '100042', '',                 '10', '850', '',       '',    ''],
            ['',              '',       'Sugar (loose)',    '50', '230', '',       '',    '260'],
        ];

        return Spreadsheet::download(self::IMPORT_COLUMNS, $samples, $format, 'goods_received_sample');
    }

    public function import(Request $request)
    {
        $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'file'          => 'required|file|max:10240',
            'notes'         => 'nullable|string|max:500',
        ]);

        if (! $branchId = CurrentBranch::requireId()) {
            return back()->withInput()->with('error', CurrentBranch::pickBranchMessage());
        }

        $file = $request->file('file');
        $type = Spreadsheet::typeFor($file->getClientOriginalExtension());
        if (! $type) {
            return back()->withInput()->with('error', 'Unsupported file. Please upload a .csv or .xlsx file.');
        }

        try {
            $rows = Spreadsheet::read($file->getRealPath(), $type);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not read the file: ' . $e->getMessage());
        }

        // Resolve every line before writing anything. A goods-received note has to
        // total the same as the supplier's invoice, so a file with an unknown
        // product is rejected outright rather than posted short.
        $items  = [];
        $errors = [];

        foreach ($rows as $n => $r) {
            $line    = $n + 2;                   // +1 header row, +1 to make it 1-based
            $barcode = trim((string) ($r['barcode'] ?? ''));
            $sku     = trim((string) ($r['sku'] ?? ''));
            $name    = trim((string) ($r['name'] ?? ''));

            if ($barcode !== '') {
                $product = Product::where('barcode', $barcode)->first();
                $label   = "barcode {$barcode}";
            } elseif ($sku !== '') {
                $product = Product::where('sku', $sku)->first();
                $label   = "SKU {$sku}";
            } elseif ($name !== '') {
                $product = Product::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
                $label   = "\"{$name}\"";
            } else {
                $errors[] = "Row {$line}: needs a barcode, SKU or product name.";
                continue;
            }

            if (! $product) {
                $errors[] = "Row {$line}: no product found for {$label} — add it under Products first.";
                continue;
            }

            $qty  = Spreadsheet::num($r['quantity'] ?? 0);
            $cost = Spreadsheet::num($r['unit_price'] ?? 0);

            if ($qty <= 0) {
                $errors[] = "Row {$line}: quantity must be greater than zero.";
                continue;
            }
            if ($cost < 0) {
                $errors[] = "Row {$line}: unit price cannot be negative.";
                continue;
            }

            $items[] = [
                'product_id' => $product->id,
                'quantity'   => $qty,
                'unit_price' => $cost,
                'batch_no'   => Spreadsheet::has($r, 'batch_no') ? trim((string) $r['batch_no']) : null,
                'mrp'        => Spreadsheet::has($r, 'mrp') ? Spreadsheet::num($r['mrp']) : '',
                'sale_price' => Spreadsheet::has($r, 'sale_price') ? Spreadsheet::num($r['sale_price']) : '',
            ];
        }

        if ($errors) {
            return view('purchases.import', [
                'suppliers' => Supplier::orderBy('name')->get(),
                'result'    => ['ok' => false, 'errors' => $errors, 'lines' => count($items)],
            ]);
        }
        if (! $items) {
            return back()->withInput()->with('error', 'That file has no goods lines in it.');
        }

        DB::beginTransaction();
        try {
            $subtotal = collect($items)->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

            // Received now, paid later: the whole amount lands on the supplier's
            // balance, and payment goes through Payment Out as usual.
            $purchase = Purchase::create([
                'bill_no'         => DocumentNumber::next('purchase'),
                'supplier_id'     => $request->supplier_id,
                'branch_id'       => $branchId,
                'user_id'         => auth()->id(),
                'subtotal'        => $subtotal,
                'discount_amount' => 0,
                'tax_amount'      => 0,
                'total'           => $subtotal,
                'paid_amount'     => 0,
                'balance_due'     => $subtotal,
                'payment_status'  => 'unpaid',
                'purchase_date'   => $request->purchase_date,
                'notes'           => $request->notes,
            ]);

            foreach ($items as $item) {
                $this->createPurchaseItem($purchase, $item, $branchId, $request->purchase_date);
            }

            $supplier = Supplier::find($request->supplier_id);
            $supplier->increment('total_purchases', $subtotal);
            $supplier->increment('balance_due', $subtotal);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Import failed: ' . $e->getMessage());
        }

        return view('purchases.import', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'result'    => [
                'ok'       => true,
                'errors'   => [],
                'lines'    => count($items),
                'units'    => collect($items)->sum('quantity'),
                'total'    => $subtotal,
                'purchase' => $purchase,
            ],
        ]);
    }


    /**
     * Create one purchase line + its cost/price layer, applying WAC (weighed) or
     * FIFO/multi-price (non-weighed) product updates. Shared by store() and update()
     * so both stay in sync.
     */
    private function createPurchaseItem(Purchase $purchase, array $item, ?int $branchId, $purchaseDate): PurchaseItem
    {
        $qty  = (float) $item['quantity'];
        $cost = (float) $item['unit_price'];

        // Custom / non-inventory line — recorded on the bill only. No product, stock or layer.
        if (empty($item['product_id'])) {
            return PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id'  => null,
                'name'        => trim($item['name'] ?? '') ?: 'Custom item',
                'quantity'    => $qty,
                'unit_price'  => $cost,
                'batch_no'    => $item['batch_no'] ?? null,
                'subtotal'    => $qty * $cost,
            ]);
        }

        $product = Product::find($item['product_id']);
        $batch   = $item['batch_no'] ?? null;
        $mrp         = isset($item['mrp']) && $item['mrp'] !== '' ? (float) $item['mrp'] : null;
        $enteredSale = isset($item['sale_price']) && $item['sale_price'] !== '' ? (float) $item['sale_price'] : null;

        // On-hand before this purchase (aggregate).
        $onHand = (float) (Stock::where('product_id', $product->id)->whereBranch($branchId)->value('quantity') ?? 0);

        if ($product->is_weighed) {
            // Weighted-average cost; the sale price is set manually (defaults to current).
            $newQty = $onHand + $qty;
            $wac = $newQty > 0
                ? (($onHand * (float) $product->purchase_price) + ($qty * $cost)) / $newQty
                : $cost;
            $product->purchase_price = round($wac, 2);
            if ($enteredSale !== null) $product->sale_price = $enteredSale;
            if ($mrp !== null)         $product->mrp = $mrp;
            $product->save();

            $layerCost = round($wac, 2);                 // weighed COGS uses WAC
            $layerSale = (float) $product->sale_price;   // weighed is single-price
        } else {
            // Non-weighed: the layer keeps its own cost + sale price. FIFO (same price,
            // varying cost) and multi-price (varying sale price) both fall out of this.
            $layerSale = $enteredSale ?? (float) $product->sale_price;
            $layerCost = $cost;
            if ($mrp !== null)         $product->mrp = $mrp;
            if ($enteredSale !== null) $product->sale_price = $enteredSale;   // default = latest
            $product->purchase_price = $cost;                                // display latest cost
            $product->save();
        }

        $purchaseItem = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id'  => $product->id,
            'quantity'    => $qty,
            'unit_price'  => $cost,
            'batch_no'    => $batch,
            'mrp'         => $mrp,
            'sale_price'  => $layerSale,
            'subtotal'    => $qty * $cost,
        ]);

        // FIFO / batch cost layer + aggregate on-hand (kept in sync centrally).
        \App\Support\Inventory::addLayer(
            $product->id, $branchId, $qty, $layerCost, $layerSale,
            $batch, $purchaseDate, $purchaseItem->id
        );

        return $purchaseItem;
    }

    /**
     * Null when the purchase is safe to edit/delete; otherwise a user-facing reason.
     * "Safe" = paid isn't final, nothing has been returned, and none of its stock has
     * left the building yet (every layer's qty_remaining still equals what it received).
     */
    private function blockingReason(Purchase $purchase): ?string
    {
        if ($purchase->returns()->exists()) {
            return 'Cannot modify a purchase that has a return recorded against it.';
        }

        $purchase->load('items.layer', 'items.product');
        foreach ($purchase->items as $item) {
            if (! $item->product_id) {
                continue;   // custom / non-inventory line — no stock to reconcile
            }
            $remaining = $item->layer ? (float) $item->layer->qty_remaining : 0.0;
            if (abs($remaining - (float) $item->quantity) > 0.0005) {
                $name = $item->product?->name ?? "product #{$item->product_id}";
                return "Cannot modify: {$remaining} of {$item->quantity} \"{$name}\" from this purchase has already been sold or moved. Use a purchase return instead.";
            }
        }

        return null;
    }
}
