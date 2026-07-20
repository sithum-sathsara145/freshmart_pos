<?php

namespace App\Http\Controllers;

use App\Support\CurrentBranch;
use App\Support\Ledger;

use App\Models\Account;
use App\Models\CounterSession;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CounterSessionController extends Controller
{
    public function index(Request $request)
    {
        $branchId = CurrentBranch::id();

        $sessions = CounterSession::with(['counter', 'openedBy', 'closedBy', 'depositAccount'])
            ->whereBranch($branchId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            // Cash counted out of a drawer that nobody has taken to a cash book yet.
            ->when($request->status === 'awaiting', fn ($q) => $q->where('status', 'closed')
                ->where('deposit_amount', '>', 0)->whereNull('deposited_at'))
            ->latest('opened_at')
            ->paginate(20)
            ->withQueryString();

        $awaiting = CounterSession::whereBranch($branchId)->where('status', 'closed')
            ->where('deposit_amount', '>', 0)->whereNull('deposited_at');

        $totals = [
            'open'            => CounterSession::whereBranch($branchId)->where('status', 'open')->count(),
            'closed'          => CounterSession::whereBranch($branchId)->where('status', 'closed')->count(),
            'variance'        => (float) CounterSession::whereBranch($branchId)->sum('variance'),
            'awaiting_count'  => (clone $awaiting)->count(),
            'awaiting_amount' => (float) (clone $awaiting)->sum('deposit_amount'),
        ];

        return view('counter-sessions.index', compact('sessions', 'totals'));
    }

    public function show(CounterSession $counterSession)
    {
        $counterSession->load(['counter', 'openedBy', 'closedBy', 'depositAccount']);

        return view('counter-sessions.show', [
            'counterSession' => $counterSession,
            'cashBooks'      => $this->cashBooksFor($counterSession),
        ]);
    }

    /**
     * Record cash actually being handed in from a closed counter.
     *
     * Closing the counter only counts the drawer and sets the surplus aside — the
     * money is still with the cashier at that point. This is where it reaches a
     * cash book, so the credit happens when somebody has really carried it there
     * rather than the moment a till was cashed up.
     */
    public function deposit(Request $request, CounterSession $counterSession)
    {
        CurrentBranch::guard($counterSession->branch_id);

        $request->validate(['account_id' => 'required|exists:accounts,id']);

        if (! $counterSession->awaitingHandIn()) {
            return back()->with('error', $counterSession->deposited_at
                ? 'This one was already handed in.'
                : 'There is nothing set aside on this session to hand in.');
        }

        $book = $this->cashBooksFor($counterSession)->firstWhere('id', (int) $request->account_id);

        if (! $book) {
            return back()->with('error', 'Pick a cash book from this branch.');
        }

        $amount    = (float) $counterSession->deposit_amount;
        $reference = 'DEP-' . strtoupper(Str::random(8));
        $counter   = $counterSession->counter?->name ?? 'counter';

        Ledger::credit($book, $amount, [
            'reference'   => $reference,
            'description' => "Cash handed in — {$counter}",
            'source_type' => 'counter_close',
            'source_id'   => $counterSession->id,
        ]);

        Payment::create([
            'reference_no' => $reference,
            'type'         => 'payment_in',
            'account_id'   => $book->id,
            'amount'       => $amount,
            'method'       => 'cash',
            'notes'        => "Cash handed in — {$counter}",
            'created_by'   => auth()->id(),
        ]);

        $counterSession->update([
            'deposit_account_id' => $book->id,
            'deposited_at'       => now(),
            'deposited_by'       => auth()->id(),
        ]);

        return back()->with('success', 'Rs. ' . number_format($amount, 2) . ' recorded into ' . $book->name . '.');
    }

    /** Cash books this session's takings could go into. */
    private function cashBooksFor(CounterSession $session)
    {
        return Account::where('type', 'cash')->where('status', 'active')
            ->where(fn ($q) => $q->whereBranch($session->branch_id)->orWhereNull('branch_id'))
            ->orderByDesc('is_cashier_book')->orderBy('name')
            ->get();
    }
}
