<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Customer;
use App\Support\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $returns = SaleReturn::with(['sale', 'customer'])
            ->whereHas('sale', fn($q) => $q->where('branch_id', $branchId))
            ->when($request->search, fn($q) => $q->where('credit_note_no', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20);

        $stats = [
            'total_returns'  => SaleReturn::whereHas('sale', fn($q) => $q->where('branch_id', $branchId))->count(),
            'total_amount'   => SaleReturn::whereHas('sale', fn($q) => $q->where('branch_id', $branchId))->sum('return_amount'),
            'this_month'     => SaleReturn::whereHas('sale', fn($q) => $q->where('branch_id', $branchId))->whereMonth('created_at', now()->month)->sum('return_amount'),
        ];

        return view('sale-returns.index', compact('returns', 'stats'));
    }

    public function create()
    {
        $branchId = auth()->user()->branch_id;

        // Candidate invoices with at least one line that still has un-returned quantity.
        // Everything the picker needs is embedded so no extra AJAX round-trip is required.
        $sales = Sale::with(['customer', 'items.product', 'items.returnItems'])
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'returned')
            ->latest()->limit(100)->get()
            ->map(function ($s) {
                $lines = $s->items->map(function ($si) {
                        $returned  = (float) $si->returnItems->sum('quantity');
                        $remaining = round((float) $si->quantity - $returned, 3);
                        return [
                            'sale_item_id' => $si->id,
                            'name'         => $si->product?->name ?? $si->name ?? 'Item',
                            'sold'         => (float) $si->quantity,
                            'returned'     => $returned,
                            'remaining'    => $remaining,
                            'unit_price'   => (float) $si->unit_price,
                        ];
                    })
                    ->filter(fn($l) => $l['remaining'] > 0.0005)
                    ->values();

                return [
                    'id'         => $s->id,
                    'invoice_no' => $s->invoice_no,
                    'customer'   => $s->customer?->name ?? 'Walk-in',
                    'total'      => (float) $s->total,
                    'lines'      => $lines,
                ];
            })
            ->filter(fn($s) => count($s['lines']) > 0)
            ->values();

        return view('sale-returns.create', compact('sales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_id'              => 'required|exists:sales,id',
            'items'                => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity'     => 'nullable|numeric|min:0',
            'reason'               => 'required|string',
            'refund_method'        => 'required|in:cash,credit_note,exchange',
        ]);

        $branchId = auth()->user()->branch_id;
        $sale     = Sale::with('items.product')->findOrFail($request->sale_id);

        if ((int) $sale->branch_id !== (int) $branchId) {
            return back()->with('error', 'That invoice belongs to another branch.')->withInput();
        }

        $saleItems = $sale->items->keyBy('id');

        // Quantity already returned per line across every prior credit note.
        $alreadyReturned = SaleReturnItem::whereIn('sale_item_id', $saleItems->keys())
            ->selectRaw('sale_item_id, SUM(quantity) as qty')
            ->groupBy('sale_item_id')
            ->pluck('qty', 'sale_item_id');

        // Keep only rows with a positive qty; validate each against its line's remaining.
        $lines = [];
        foreach ($request->items as $row) {
            $qty = (float) ($row['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $si = $saleItems->get((int) $row['sale_item_id']);
            if (! $si) {
                return back()->with('error', 'A returned item does not belong to the selected invoice.')->withInput();
            }
            $prev      = (float) ($alreadyReturned[$si->id] ?? 0);
            $remaining = (float) $si->quantity - $prev;
            if ($qty - $remaining > 0.0005) {
                $name = $si->product?->name ?? $si->name ?? 'item';
                return back()->with('error', "You can return at most {$remaining} of \"{$name}\" (already returned {$prev} of {$si->quantity}).")->withInput();
            }
            $lines[] = ['si' => $si, 'qty' => $qty];
        }

        if (empty($lines)) {
            return back()->with('error', 'Enter a return quantity for at least one item.')->withInput();
        }

        DB::beginTransaction();
        try {
            $returnAmount = round(collect($lines)->sum(fn($l) => $l['qty'] * (float) $l['si']->unit_price), 2);

            $saleReturn = SaleReturn::create([
                'credit_note_no' => $this->nextCreditNoteNo(),
                'sale_id'        => $sale->id,
                'customer_id'    => $sale->customer_id,
                'reason'         => $request->reason,
                'return_amount'  => $returnAmount,
                'refund_method'  => $request->refund_method,
                'created_by'     => auth()->id(),
            ]);

            foreach ($lines as $l) {
                $si  = $l['si'];
                $qty = $l['qty'];

                // Reverse the exact COGS captured on the original sale line (per-unit).
                $perUnitCost = ($si->cost !== null && (float) $si->quantity > 0)
                    ? (float) $si->cost / (float) $si->quantity
                    : (float) ($si->product?->purchase_price ?? 0);
                $lineCost = round($perUnitCost * $qty, 2);

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'product_id'     => $si->product_id,
                    'sale_item_id'   => $si->id,
                    'quantity'       => $qty,
                    'unit_price'     => $si->unit_price,
                    'cost'           => $lineCost,
                    'subtotal'       => round($qty * (float) $si->unit_price, 2),
                ]);

                // Return stock as a fresh layer, re-sellable at the original cost + sold price.
                if ($si->product_id) {
                    Inventory::addLayer(
                        $si->product_id, $sale->branch_id, $qty,
                        $perUnitCost, (float) $si->unit_price,
                        'RETURN', now()->toDateString()
                    );
                }
            }

            // A cash refund actually leaves the till (mirror of the sale's payment_in).
            if ($request->refund_method === 'cash' && $returnAmount > 0) {
                $account = Account::where('branch_id', $branchId)->where('type', 'cash')->first()
                        ?? Account::where('branch_id', $branchId)->first();
                if ($account) {
                    Payment::create([
                        'reference_no' => 'REF-' . strtoupper(Str::random(8)),
                        'type'         => 'payment_out',
                        'account_id'   => $account->id,
                        'party_type'   => 'customer',
                        'party_id'     => $sale->customer_id,
                        'sale_id'      => $sale->id,
                        'amount'       => $returnAmount,
                        'method'       => 'cash',
                        'notes'        => "Refund for {$saleReturn->credit_note_no}",
                        'created_by'   => auth()->id(),
                    ]);
                    $account->decrement('balance', $returnAmount);
                }
            }

            // Flip the sale to 'returned' only once every line is fully returned.
            if ($this->isFullyReturned($sale)) {
                $sale->update(['status' => 'returned']);
            }

            // Claw back loyalty proportionally.
            if ($sale->customer_id) {
                $pts = (int) ($returnAmount / 20);
                if ($pts > 0) {
                    Customer::where('id', $sale->customer_id)->decrement('loyalty_points', $pts);
                }
            }

            DB::commit();
            return redirect()->route('sale-returns.show', $saleReturn)->with('success', "Credit Note #{$saleReturn->credit_note_no} issued.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Return failed: ' . $e->getMessage())->withInput();
        }
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['items.product', 'sale.customer', 'customer']);
        return view('sale-returns.show', compact('saleReturn'));
    }

    public function destroy(SaleReturn $saleReturn)
    {
        $branchId = auth()->user()->branch_id;
        $saleReturn->load(['items.product', 'sale']);
        $sale = $saleReturn->sale;

        if (! $sale || (int) $sale->branch_id !== (int) $branchId) {
            return back()->with('error', 'Return not found for this branch.');
        }

        // We can only cleanly reverse while the re-added stock is still on hand.
        foreach ($saleReturn->items as $item) {
            $onHand = (float) (Stock::where('product_id', $item->product_id)->where('branch_id', $branchId)->value('quantity') ?? 0);
            if ($onHand + 0.0005 < (float) $item->quantity) {
                return back()->with('error', 'Cannot reverse: some returned stock has since been sold. Adjust stock manually instead.');
            }
        }

        DB::beginTransaction();
        try {
            // Pull the returned stock back out (FIFO); aggregate + layers stay in sync.
            foreach ($saleReturn->items as $item) {
                if ($item->product_id && $item->product) {
                    Inventory::consume($item->product, $branchId, (float) $item->quantity, (float) $item->unit_price);
                }
            }

            // Undo a cash refund if one was paid out.
            if ($saleReturn->refund_method === 'cash') {
                $payments = Payment::where('sale_id', $sale->id)
                    ->where('type', 'payment_out')
                    ->where('notes', 'like', '%' . $saleReturn->credit_note_no . '%')
                    ->get();
                foreach ($payments as $p) {
                    Account::where('id', $p->account_id)->increment('balance', $p->amount);
                    $p->delete();
                }
            }

            // Give loyalty points back.
            if ($sale->customer_id) {
                $pts = (int) ($saleReturn->return_amount / 20);
                if ($pts > 0) {
                    Customer::where('id', $sale->customer_id)->increment('loyalty_points', $pts);
                }
            }

            $creditNoteNo = $saleReturn->credit_note_no;
            $saleReturn->items()->delete();
            $saleReturn->delete();

            // The sale may no longer be fully returned.
            if ($sale->status === 'returned' && ! $this->isFullyReturned($sale)) {
                $sale->update(['status' => (float) $sale->paid_amount >= (float) $sale->total ? 'paid' : 'partial']);
            }

            DB::commit();
            return redirect()->route('sale-returns.index')->with('success', "Return {$creditNoteNo} reversed.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Reverse failed: ' . $e->getMessage());
        }
    }

    /** True when every line of the sale has been fully returned. */
    private function isFullyReturned(Sale $sale): bool
    {
        $sale->loadMissing('items');
        if ($sale->items->isEmpty()) {
            return false;
        }
        $returned = SaleReturnItem::whereIn('sale_item_id', $sale->items->pluck('id'))
            ->selectRaw('sale_item_id, SUM(quantity) as qty')
            ->groupBy('sale_item_id')
            ->pluck('qty', 'sale_item_id');

        foreach ($sale->items as $si) {
            if ((float) ($returned[$si->id] ?? 0) + 0.0005 < (float) $si->quantity) {
                return false;
            }
        }
        return true;
    }

    private function nextCreditNoteNo(): string
    {
        $last = SaleReturn::latest('id')->value('credit_note_no');
        $num  = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;
        return 'CR-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
