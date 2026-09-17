<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Services\AccountNumberService;
use App\Services\CashTransactionService;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Income::class, 'income');
    }

    public function index(Request $request)
    {
        $query = Income::with(['creator', 'category']);

        if ($request->filled('date_from')) {
            $query->whereDate('income_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('income_date', '<=', $request->input('date_to'));
        }

        $incomes = $query->orderByDesc('income_date')->paginate(20)->withQueryString();

        $total = Income::where('status', 'posted')
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('income_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('income_date', '<=', $request->input('date_to')))
            ->sum('amount');

        $categories = IncomeCategory::active()->orderBy('name')->get();

        return view('cash.incomes.index', compact('incomes', 'total', 'categories'));
    }

    public function create()
    {
        return view('cash.incomes.form', [
            'income' => new Income,
            'categories' => IncomeCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'income_date' => 'required|date',
            'income_category_id' => 'nullable|exists:income_categories,id',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'source' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data['income_no'] = app(AccountNumberService::class)->nextTransactionNumber('income', 'INC-', true, $data['income_date']);
        $data['created_by'] = auth()->id();

        $income = Income::create($data);

        app(CashTransactionService::class)->record(
            register: app(CashTransactionService::class)->openRegister($data['income_date']),
            type: 'other_income',
            direction: 'in',
            amount: (float) $data['amount'],
            source: $income,
            paymentMethod: $data['payment_method'],
            reference: $income->income_no,
            notes: $income->category?->name ?? __('Income'),
        );

        AuditLog::record('income.created', $income, [], $income->toArray());

        return redirect()->route('cash.incomes.index')->with('success', __('Income recorded successfully.'));
    }

    public function edit(Income $income)
    {
        return view('cash.incomes.form', [
            'income' => $income,
            'categories' => IncomeCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Income $income)
    {
        $data = $request->validate([
            'income_date' => 'required|date',
            'income_category_id' => 'nullable|exists:income_categories,id',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'source' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $before = $income->toArray();
        $income->update($data);

        app(CashTransactionService::class)->updateSourceAmount($income, (float) $data['amount']);

        AuditLog::record('income.updated', $income, $before, $income->toArray());

        return redirect()->route('cash.incomes.index')->with('success', __('Income updated successfully.'));
    }

    public function destroy(Income $income)
    {
        app(CashTransactionService::class)->deleteSource($income);

        AuditLog::record('income.deleted', $income, $income->toArray(), []);
        $income->delete();

        return redirect()->route('cash.incomes.index')->with('success', __('Income deleted. The linked cash entry was also removed.'));
    }
}