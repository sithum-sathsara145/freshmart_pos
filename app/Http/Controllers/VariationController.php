<?php

namespace App\Http\Controllers;

use App\Models\VariationType;
use App\Models\VariationValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VariationController extends Controller
{
    public function index()
    {
        $types = VariationType::with('values')->orderBy('name')->get();
        return view('variations.index', compact('types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:100|unique:variation_types,name',
            'values' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $type = VariationType::create(['name' => $request->name]);
            $this->syncValues($type, $request->values);
        });

        return redirect()->route('variations.index')->with('success', 'Variation added.');
    }

    public function edit(VariationType $variation)
    {
        $variation->load('values');
        return view('variations.edit', compact('variation'));
    }

    public function update(Request $request, VariationType $variation)
    {
        $request->validate([
            'name'   => 'required|string|max:100|unique:variation_types,name,' . $variation->id,
            'values' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $variation) {
            $variation->update(['name' => $request->name]);
            $variation->values()->delete();
            $this->syncValues($variation, $request->values);
        });

        return redirect()->route('variations.index')->with('success', 'Variation updated.');
    }

    public function destroy(VariationType $variation)
    {
        DB::transaction(function () use ($variation) {
            $variation->values()->delete();
            $variation->delete();
        });
        return back()->with('success', 'Variation deleted.');
    }

    // Turn a comma-separated string ("Small, Medium, Large") into VariationValue rows.
    private function syncValues(VariationType $type, ?string $values): void
    {
        collect(explode(',', (string) $values))
            ->map(fn($v) => trim($v))
            ->filter()
            ->unique()
            ->each(fn($v) => VariationValue::create([
                'variation_type_id' => $type->id,
                'value'             => $v,
            ]));
    }
}
