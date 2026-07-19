<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);

        $holidays = Holiday::whereYear('date', $year)->orderBy('date')->get();
        $upcoming = Holiday::whereDate('date', '>=', today())->orderBy('date')->limit(5)->get();

        // Holidays are a short per-year list, so show the whole year rather than
        // paginating — the previous paginate(30) had no links() and simply hid
        // everything past the 30th holiday.
        $years = Holiday::selectRaw('DISTINCT YEAR(`date`) as y')->orderByDesc('y')->pluck('y');

        return view('hrm.holidays.index', compact('holidays', 'upcoming', 'year', 'years'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'date' => ['required', 'date', Rule::unique('holidays', 'date')],
            'type' => 'required|in:public,company',
        ], [
            'date.unique' => 'A holiday is already recorded for that date.',
        ]);

        Holiday::create($request->only(['name', 'date', 'type']));

        return back()->with('success', 'Holiday added.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }
}
