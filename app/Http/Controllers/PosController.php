<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Stock;
use App\Models\StockLayer;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Coupon;
use App\Models\Counter;
use App\Models\CounterSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    // Sri Lankan rupee denominations (notes then coins)
    private const DENOMINATIONS = [5000, 2000, 1000, 500, 100, 50, 20, 10, 5, 2, 1];

    public function index()
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $branch = auth()->user()->branch;
        $counter = auth()->user()->counter;

        $openSession = null;
        $lastClose   = null;
        if ($counter) {
            $openSession = CounterSession::where('counter_id', $counter->id)
                ->where('status', 'open')->latest('opened_at')->first();

            if ($openSession) {
                // live expected cash so far = opening + cash sales since open
                $openSession->cash_sales_so_far = $this->cashSalesSince($counter->id, $openSession->opened_at);
            } else {
                $lastClose = CounterSession::where('counter_id', $counter->id)
                    ->where('status', 'closed')->latest('closed_at')->first();
            }
        }

        return view('pos.index', compact('categories', 'branch', 'counter', 'openSession', 'lastClose')
            + ['denominations' => self::DENOMINATIONS]);
    }

    // Cash collected at a counter since a given time (what should be in the drawer from sales)
    private function cashSalesSince(int $counterId, $since): float
    {
        return (float) Sale::where('counter_id', $counterId)
            ->where('payment_method', 'cash')
            ->where('created_at', '>=', $since)
            ->sum('paid_amount');
    }

    // Open the counter for the current session with a counted opening float
    public function openCounter(Request $request)
    {
        $counter = auth()->user()->counter;
        if (! $counter) {
            return response()->json(['success' => false, 'message' => 'No counter assigned to your account.'], 422);
        }

        if (CounterSession::where('counter_id', $counter->id)->where('status', 'open')->exists()) {
            return response()->json(['success' => false, 'message' => 'Counter is already open.'], 422);
        }

        $denoms  = $this->cleanDenoms($request->input('denoms', []));
        $opening = $this->denomsTotal($denoms);

        DB::transaction(function () use ($counter, $denoms, $opening) {
            CounterSession::create([
                'counter_id'      => $counter->id,
                'branch_id'       => $counter->branch_id,
                'opened_by'       => auth()->id(),
                'opening_balance' => $opening,
                'opening_denoms'  => $denoms,
                'status'          => 'open',
                'opened_at'       => now(),
            ]);
            $counter->update(['status' => 'open', 'cash_balance' => $opening]);
        });

        return response()->json(['success' => true, 'opening' => $opening, 'message' => 'Counter opened.']);
    }

    // Close the counter: reconcile counted cash against opening + cash sales
    public function closeCounter(Request $request)
    {
        $counter = auth()->user()->counter;
        $session = $counter
            ? CounterSession::where('counter_id', $counter->id)->where('status', 'open')->latest('opened_at')->first()
            : null;

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'No open counter session to close.'], 422);
        }

        $denoms    = $this->cleanDenoms($request->input('denoms', []));
        $counted   = $this->denomsTotal($denoms);
        $cashSales = $this->cashSalesSince($counter->id, $session->opened_at);
        $expected  = (float) $session->opening_balance + $cashSales;
        $variance  = round($counted - $expected, 2);

        DB::transaction(function () use ($session, $counter, $denoms, $counted, $cashSales, $expected, $variance) {
            $session->update([
                'closed_by'        => auth()->id(),
                'closing_denoms'   => $denoms,
                'closing_balance'  => $counted,
                'cash_sales'       => $cashSales,
                'expected_closing' => $expected,
                'variance'         => $variance,
                'status'           => 'closed',
                'closed_at'        => now(),
            ]);
            $counter->update(['status' => 'closed', 'cash_balance' => $counted]);
        });

        return response()->json([
            'success'   => true,
            'opening'   => (float) $session->opening_balance,
            'cash_sales'=> $cashSales,
            'expected'  => $expected,
            'counted'   => $counted,
            'variance'  => $variance,
            'message'   => 'Counter closed.',
        ]);
    }

    // Keep only valid denomination => quantity pairs
    private function cleanDenoms(array $input): array
    {
        $out = [];
        foreach (self::DENOMINATIONS as $d) {
            $qty = (int) ($input[$d] ?? 0);
            if ($qty > 0) $out[$d] = $qty;
        }
        return $out;
    }

    private function denomsTotal(array $denoms): float
    {
        $total = 0;
        foreach ($denoms as $denom => $qty) {
            $total += (int) $denom * (int) $qty;
        }
        return (float) $total;
    }

    // Ajax: search products for POS screen
    public function searchProducts(Request $request)
    {
        $q = $request->get('q', '');
        $category = $request->get('category');
        $branchId = auth()->user()->branch_id;

        $products = Product::with(['category', 'brand'])
            ->where('status', 'active')
            ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%$q%")
                      ->orWhere('barcode', 'like', "%$q%")
                      ->orWhere('sku', 'like', "%$q%");
            }))
            ->when($category, fn($query) => $query->where('category_id', $category))
            ->select('id', 'name', 'barcode', 'sale_price', 'tax_percent', 'image', 'category_id', 'unit')
            ->limit(30)
            ->get()
            ->map(function ($product) use ($branchId) {
                $stock = Stock::where('product_id', $product->id)
                              ->where('branch_id', $branchId)
                              ->value('quantity') ?? 0;
                return [
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'barcode'     => $product->barcode,
                    'price'       => $product->sale_price,
                    'tax_percent' => $product->tax_percent,
                    'unit'        => $product->unit,
                    'stock'       => $stock,
                    'image'       => $product->image ? asset('storage/' . $product->image) : null,
                    'category'    => $product->category?->name,
                ];
            });

        return response()->json($products);
    }

    // Ajax: find product by barcode (scanner)
    public function findByBarcode(string $barcode)
    {
        $branchId = auth()->user()->branch_id;

        // 1) Exact match first — covers manufacturer barcodes AND our internal/custom
        //    store barcodes. Doing this before scale-parsing means a stored barcode can
        //    never be mis-read as a scale code.
        $product = Product::where('barcode', $barcode)
                          ->where('status', 'active')
                          ->first();

        if ($product) {
            $stock = Stock::where('product_id', $product->id)
                          ->where('branch_id', $branchId)
                          ->value('quantity') ?? 0;

            $priceOptions = $product->is_weighed ? [] : StockLayer::where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->where('qty_remaining', '>', 0)
                ->distinct()
                ->orderBy('sale_price')
                ->pluck('sale_price')
                ->map(fn($v) => (float) $v)
                ->all();

            return response()->json([
                'id'          => $product->id,
                'name'        => $product->name,
                'barcode'     => $product->barcode,
                'price'       => $product->sale_price,
                'price_options' => $priceOptions,
                'is_weighed'  => (bool) $product->is_weighed,
                'tax_percent' => $product->tax_percent,
                'unit'        => $product->unit,
                'stock'       => $stock,
            ]);
        }

        // 2) Scale / weighed embedded barcode (prefix "2")? Resolve by PLU and read the
        //    embedded weight or price. Returns null for ordinary barcodes.
        $scale = \App\Support\ScaleBarcode::parse($barcode);
        if ($scale) {
            $product = Product::where('is_weighed', true)
                              ->whereRaw('CAST(scale_plu AS UNSIGNED) = ?', [(int) $scale['plu']])
                              ->where('status', 'active')
                              ->first();

            if (! $product) {
                return response()->json(['message' => "No weighed product for PLU {$scale['plu']}."], 404);
            }

            $unitPrice = (float) $product->sale_price;
            if ($scale['embed'] === 'weight') {
                $qty = round($scale['value'], 3);                                   // value is the weight
            } else {
                $qty = $unitPrice > 0 ? round($scale['value'] / $unitPrice, 3) : 0; // value is the line price
            }

            $stock = Stock::where('product_id', $product->id)->where('branch_id', $branchId)->value('quantity') ?? 0;

            return response()->json([
                'id'          => $product->id,
                'name'        => $product->name,
                'barcode'     => $product->barcode,
                'price'       => $unitPrice,
                'tax_percent' => $product->tax_percent,
                'unit'        => $product->unit,
                'stock'       => $stock,
                'weighed'     => true,
                'qty'         => $qty,
            ]);
        }

        // 3) Nothing matched.
        return response()->json(['message' => 'No product found.'], 404);
    }

    // Process sale from POS
    public function storeSale(Request $request)
    {
        $request->validate([
            'items'          => 'required|array|min:1',
            'items.*.id'     => 'required|exists:products,id',
            'items.*.qty'    => 'required|numeric|min:0.001',
            'items.*.price'  => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,credit,mixed',
            'paid_amount'    => 'required|numeric|min:0',
            'discount_amount'=> 'nullable|numeric|min:0',
            'tax_amount'     => 'nullable|numeric|min:0',
            'card_last4'     => 'nullable|digits:4',
        ]);

        // An assigned counter must have an open session before any sale
        $counterId = auth()->user()->counter_id;
        if ($counterId && ! CounterSession::where('counter_id', $counterId)->where('status', 'open')->exists()) {
            return response()->json(['success' => false, 'message' => 'Open the counter before making sales.'], 422);
        }

        DB::beginTransaction();

        try {
            $branchId  = auth()->user()->branch_id;
            $counterId = auth()->user()->counter_id;
            $userId    = auth()->id();

            // Validate coupon if provided
            $coupon = null;
            $discountAmount = 0;
            if ($request->coupon_code) {
                $coupon = Coupon::where('code', $request->coupon_code)
                                ->where('status', 'active')
                                ->whereDate('expires_at', '>=', today())
                                ->first();

                if ($coupon && ($coupon->max_uses === null || $coupon->used_count < $coupon->max_uses)) {
                    if ($coupon->type === 'percentage') {
                        $discountAmount = round($request->subtotal * $coupon->value / 100, 2);
                    } else {
                        $discountAmount = min($coupon->value, $request->subtotal);
                    }
                }
            }

            // Calculate totals
            $subtotal   = collect($request->items)->sum(fn($item) => $item['price'] * $item['qty']);
            $taxAmount  = collect($request->items)->sum(function ($item) {
                return ($item['price'] * $item['qty']) * ($item['tax_percent'] ?? 0) / 100;
            });
            // Manual discount / tax entered at the POS take precedence
            if ($request->filled('discount_amount')) {
                $discountAmount = max(0, (float) $request->discount_amount);
            }
            if ($request->filled('tax_amount')) {
                $taxAmount = max(0, (float) $request->tax_amount);
            }

            $total      = max(0, $subtotal - $discountAmount + $taxAmount);
            $paidAmount = min($request->paid_amount, $total);
            $change     = max(0, $request->paid_amount - $total);

            // Create sale
            $sale = Sale::create([
                'invoice_no'     => $this->generateInvoiceNo(),
                'customer_id'    => $request->customer_id,
                'branch_id'      => $branchId,
                'counter_id'     => $counterId,
                'user_id'        => $userId,
                'coupon_id'      => $coupon?->id,
                'subtotal'       => $subtotal,
                'discount_amount'=> $discountAmount,
                'tax_amount'     => $taxAmount,
                'total'          => $total,
                'paid_amount'    => $paidAmount,
                'change_amount'  => $change,
                'payment_method' => $request->payment_method,
                'status'         => $paidAmount >= $total ? 'paid' : 'partial',
                'notes'          => $request->notes,
            ]);

            // Sale items + stock deduction (consume FIFO/WAC cost layers for COGS)
            foreach ($request->items as $item) {
                $product = Product::find($item['id']);
                $qty     = (float) $item['qty'];
                $cogs    = $product
                    ? \App\Support\Inventory::consume($product, $branchId, $qty, isset($item['price']) ? (float) $item['price'] : null)
                    : 0;

                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $item['id'],
                    'quantity'     => $qty,
                    'unit_price'   => $item['price'],
                    'cost'         => $cogs,
                    'tax_percent'  => $item['tax_percent'] ?? 0,
                    'subtotal'     => $item['price'] * $qty,
                ]);
            }

            // Update coupon usage
            if ($coupon) {
                $coupon->increment('used_count');
            }

            // Add loyalty points (1 point per Rs. 20)
            if ($request->customer_id) {
                $points = (int) ($total / 20);
                Customer::find($request->customer_id)->increment('loyalty_points', $points);
                Customer::find($request->customer_id)->increment('total_purchases', $total);
            }

            // Payment record
            if ($paidAmount > 0) {
                $account = Account::where('branch_id', $branchId)
                                  ->where('type', 'cash')
                                  ->first();
                if ($account) {
                    // For card payments, use the last 4 digits as the transaction reference
                    $reference = ($request->payment_method === 'card' && $request->filled('card_last4'))
                        ? 'CARD-' . $request->card_last4 . '-' . $sale->id
                        : 'PAY-' . strtoupper(Str::random(8));

                    Payment::create([
                        'reference_no' => $reference,
                        'type'         => 'payment_in',
                        'account_id'   => $account->id,
                        'party_type'   => 'customer',
                        'party_id'     => $request->customer_id,
                        'sale_id'      => $sale->id,
                        'amount'       => $paidAmount,
                        'method'       => $request->payment_method,
                        'created_by'   => $userId,
                    ]);

                    $account->increment('balance', $paidAmount);
                }
            }

            // Update counter cash
            if ($counterId && $request->payment_method === 'cash') {
                \App\Models\Counter::find($counterId)->increment('cash_balance', $paidAmount);
            }

            DB::commit();

            return response()->json([
                'success'    => true,
                'sale_id'    => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'total'      => $total,
                'change'     => $change,
                'message'    => 'Sale completed successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Sale failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function receipt(int $id)
    {
        $sale = Sale::with(['items.product', 'customer', 'branch', 'user'])
                    ->findOrFail($id);

        $settings = \App\Models\Setting::pluck('value', 'key_name');

        return view('pos.receipt', compact('sale', 'settings'));
    }

    private function generateInvoiceNo(): string
    {
        $last = Sale::latest()->value('invoice_no');
        $num  = $last ? ((int) substr($last, 4)) + 1 : 1;
        return 'INV-' . str_pad($num, 6, '0', STR_PAD_LEFT);
    }

}
