<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ExpenseCategory::class, 'category');
    }

    public function index()
    {
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();

        return view('cash.expense-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('cash.expense-categories.form', ['category' => new ExpenseCategory]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $category = ExpenseCategory::create($data);
        AuditLog::record('expense_category.created', $category, [], $category->toArray());

        return redirect()->route('cash.expense-categories.index')->with('success', __('Expense category created successfully.'));
    }

    public function edit(ExpenseCategory $category)
    {
        return view('cash.expense-categories.form', compact('category'));
    }

    public function update(Request $request, ExpenseCategory $category)
    {
        $data = $this->validateData($request);

        $before = $category->toArray();
        $category->update($data);
        AuditLog::record('expense_category.updated', $category, $before, $category->toArray());

        return redirect()->route('cash.expense-categories.index')->with('success', __('Expense category updated successfully.'));
    }

    public function destroy(ExpenseCategory $category)
    {
        if ($category->expenses()->exists()) {
            return back()->with('error', __('Category cannot be deleted because it has expenses.'));
        }

        AuditLog::record('expense_category.deleted', $category, $category->toArray(), []);
        $category->delete();

        return redirect()->route('cash.expense-categories.index')->with('success', __('Expense category deleted successfully.'));
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($request->route('category'))],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
    }
}
