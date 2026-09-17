<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncomeCategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(IncomeCategory::class, 'category');
    }

    public function index()
    {
        $categories = IncomeCategory::withCount('incomes')->orderBy('name')->get();

        return view('cash.income-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('cash.income-categories.form', ['category' => new IncomeCategory]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $category = IncomeCategory::create($data);
        AuditLog::record('income_category.created', $category, [], $category->toArray());

        return redirect()->route('cash.income-categories.index')->with('success', __('Income category created successfully.'));
    }

    public function edit(IncomeCategory $category)
    {
        return view('cash.income-categories.form', compact('category'));
    }

    public function update(Request $request, IncomeCategory $category)
    {
        $data = $this->validateData($request);

        $before = $category->toArray();
        $category->update($data);
        AuditLog::record('income_category.updated', $category, $before, $category->toArray());

        return redirect()->route('cash.income-categories.index')->with('success', __('Income category updated successfully.'));
    }

    public function destroy(IncomeCategory $category)
    {
        if ($category->incomes()->exists()) {
            return back()->with('error', __('Category cannot be deleted because it has income records.'));
        }

        AuditLog::record('income_category.deleted', $category, $category->toArray(), []);
        $category->delete();

        return redirect()->route('cash.income-categories.index')->with('success', __('Income category deleted successfully.'));
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('income_categories', 'name')->ignore($request->route('category'))],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
    }
}
