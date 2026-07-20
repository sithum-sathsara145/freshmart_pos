<?php

namespace App\Http\Controllers;

use App\Support\CurrentBranch;

use App\Models\CounterSession;
use Illuminate\Http\Request;

class CounterSessionController extends Controller
{
    public function index(Request $request)
    {
        $branchId = CurrentBranch::id();

        $sessions = CounterSession::with(['counter', 'openedBy', 'closedBy'])
            ->whereBranch($branchId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('opened_at')
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'open'     => CounterSession::whereBranch($branchId)->where('status', 'open')->count(),
            'closed'   => CounterSession::whereBranch($branchId)->where('status', 'closed')->count(),
            'variance' => (float) CounterSession::whereBranch($branchId)->sum('variance'),
        ];

        return view('counter-sessions.index', compact('sessions', 'totals'));
    }

    public function show(CounterSession $counterSession)
    {
        $counterSession->load(['counter', 'openedBy', 'closedBy', 'depositAccount']);
        return view('counter-sessions.show', compact('counterSession'));
    }
}
