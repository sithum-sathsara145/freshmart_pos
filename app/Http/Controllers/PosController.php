<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Stock;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function index()
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $branch = auth()->user()->branch;
        $counter = auth()->user()->counter;

        return view('pos.index', compact('categories', 'branch', 'counter'));
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
        $product = Product::where('barcode', $barcode)
                          ->where('status', 'active')
                          ->firstOrFail();

        $branchId = auth()->user()->branch_id;
        $stock = Stock::where('product_id', $product->id)
                      ->where('branch_id', $branchId)
                      ->value('quantity') ?? 0;

        return response()->json([
            'id'          => $product->id,
            'name'        => $product->name,
            'barcode'     => $product->barcode,
            'price'       => $product->sale_price,
            'tax_percent' => $product->tax_percent,
            'unit'        => $product->unit,
            'stock'       => $stock,
        ]);
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
        ]);

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
            $total      = $subtotal - $discountAmount + $taxAmount;
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

            // Sale items + stock deduction
            foreach ($request->items as $item) {
                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $item['id'],
                    'quantity'     => $item['qty'],
                    'unit_price'   => $item['price'],
                    'tax_percent'  => $item['tax_percent'] ?? 0,
                    'subtotal'     => $item['price'] * $item['qty'],
                ]);

                // Deduct stock
                Stock::where('product_id', $item['id'])
                     ->where('branch_id', $branchId)
                     ->decrement('quantity', $item['qty']);
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
                    Payment::create([
                        'reference_no' => 'PAY-' . strtoupper(Str::random(8)),
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
