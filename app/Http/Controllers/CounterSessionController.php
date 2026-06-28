<?php

namespace App\Http\Controllers;

use App\Models\CounterSession;
use Illuminate\Http\Request;

class CounterSessionController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $sessions = CounterSession::with(['counter', 'openedBy', 'closedBy'])
            ->where('branch_id', $branchId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('opened_at')
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'open'     => CounterSession::where('branch_id', $branchId)->where('status', 'open')->count(),
            'closed'   => CounterSession::where('branch_id', $branchId)->where('status', 'closed')->count(),
            'variance' => (float) CounterSession::where('branch_id', $branchId)->sum('variance'),
        ];

        return view('counter-sessions.index', compact('sessions', 'totals'));
    }

    public function show(CounterSession $counterSession)
    {
        $counterSession->load(['counter', 'openedBy', 'closedBy']);
        return view('counter-sessions.show', compact('counterSession'));
    }
}
