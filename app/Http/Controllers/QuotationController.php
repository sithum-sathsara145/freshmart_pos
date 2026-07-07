<?php
namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $quotations = Quotation::with(['customer', 'user'])
            ->where('branch_id', auth()->user()->branch_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(20);

        $stats = [
            'total'     => Quotation::where('branch_id', auth()->user()->branch_id)->count(),
            'pending'   => Quotation::where('branch_id', auth()->user()->branch_id)->where('status', 'pending')->count(),
            'converted' => Quotation::where('branch_id', auth()->user()->branch_id)->where('status', 'converted')->count(),
        ];

        return view('quotations.index', compact('quotations', 'stats'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products  = Product::where('status', 'active')->orderBy('name')->get()
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->sale_price, 'barcode' => $p->barcode]);
        return view('quotations.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        // Drop blank rows (added but no product chosen) before validating.
        $request->merge([
            'items' => collect($request->items ?? [])->filter(fn($i) => ! empty($i['product_id']))->values()->all(),
        ]);

        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $discount = (float)($request->discount_amount ?? 0);
            $tax      = (float)($request->tax_amount ?? 0);
            $total    = $subtotal - $discount + $tax;

            $quote = Quotation::create([
                'quote_no'        => $this->nextQuoteNo(),
                'customer_id'     => $request->customer_id,
                'branch_id'       => auth()->user()->branch_id,
                'user_id'         => auth()->id(),
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => $tax,
                'total'           => $total,
                'valid_till'      => $request->valid_till,
                'notes'           => $request->notes,
                'status'          => 'pending',
            ]);

            foreach ($request->items as $item) {
                QuotationItem::create([
                    'quotation_id' => $quote->id,
                    'product_id'   => $item['product_id'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'subtotal'     => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();
            return redirect()->route('quotations.index')->with('success', "Quotation #{$quote->quote_no} created.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(Quotation $quotation)
    {
        abort_if((int) $quotation->branch_id !== (int) auth()->user()->branch_id, 404);
        $quotation->load(['items.product', 'customer']);
        return view('quotations.show', compact('quotation'));
    }

    public function destroy(Quotation $quotation)
    {
        if ((int) $quotation->branch_id !== (int) auth()->user()->branch_id) {
            return back()->with('error', 'Quotation not found for this branch.');
        }

        DB::beginTransaction();
        try {
            // quotation_items reference the quote (InnoDB FK, no cascade) — remove them first.
            $quotation->items()->delete();
            $quotation->delete();
            DB::commit();
            return redirect()->route('quotations.index')->with('success', 'Quotation deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    public function convertToSale(int $id)
    {
        $quote = Quotation::where('branch_id', auth()->user()->branch_id)->findOrFail($id);
        if ($quote->status === 'converted') {
            return back()->with('error', 'This quotation has already been converted.');
        }
        // Load the quote into a new sale for review; it is only marked 'converted'
        // once that sale is actually saved (SaleController::store()).
        return redirect()->route('sales.create', ['from_quote' => $quote->id]);
    }

    public function pdf(int $id)
    {
        $quotation = Quotation::with(['items.product', 'customer', 'branch'])
            ->where('branch_id', auth()->user()->branch_id)->findOrFail($id);
        $settings  = \App\Models\Setting::pluck('value', 'key_name');
        $pdf       = Pdf::loadView('quotations.pdf', compact('quotation', 'settings'))->setPaper('A4');
        return $pdf->download("Quote-{$quotation->quote_no}.pdf");
    }

    private function nextQuoteNo(): string
    {
        $last = Quotation::latest('id')->value('quote_no');
        $num  = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;
        return 'QT-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
