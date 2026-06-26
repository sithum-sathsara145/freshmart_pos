<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = ExpenseCategory::when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%"))
            ->withCount('expenses')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('expense-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:150|unique:expense_categories,name']);
        ExpenseCategory::create($request->only(['name', 'description']));
        return redirect()->route('expense-categories.index')->with('success', 'Expense category added.');
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        return view('expense-categories.edit', ['category' => $expenseCategory]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $request->validate(['name' => 'required|string|max:150|unique:expense_categories,name,' . $expenseCategory->id]);
        $expenseCategory->update($request->only(['name', 'description']));
        return redirect()->route('expense-categories.index')->with('success', 'Expense category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->exists()) {
            return back()->with('error', 'Cannot delete — category has expenses.');
        }
        $expenseCategory->delete();
        return back()->with('success', 'Expense category deleted.');
    }
}
