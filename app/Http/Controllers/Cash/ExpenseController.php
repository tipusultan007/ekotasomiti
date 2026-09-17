<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\AccountNumberService;
use App\Services\CashTransactionService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Expense::class, 'expense');
    }

    public function index(Request $request)
    {
        $query = Expense::with(['creator', 'category']);

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->input('date_to'));
        }

        $expenses = $query->orderByDesc('expense_date')->paginate(20)->withQueryString();

        $total = Expense::where('status', 'posted')
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('expense_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('expense_date', '<=', $request->input('date_to')))
            ->sum('amount');

        $categories = ExpenseCategory::active()->orderBy('name')->get();

        return view('cash.expenses.index', compact('expenses', 'total', 'categories'));
    }

    public function create()
    {
        return view('cash.expenses.form', [
            'expense' => new Expense,
            'categories' => ExpenseCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'payee' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data['expense_no'] = app(AccountNumberService::class)->nextTransactionNumber('expense', 'EXP-', true, $data['expense_date']);
        $data['created_by'] = auth()->id();

        $expense = Expense::create($data);

        app(CashTransactionService::class)->record(
            register: app(CashTransactionService::class)->openRegister($data['expense_date']),
            type: 'expense',
            direction: 'out',
            amount: (float) $data['amount'],
            source: $expense,
            paymentMethod: $data['payment_method'],
            reference: $expense->expense_no,
            notes: $expense->category?->name ?? __('Expense'),
        );

        AuditLog::record('expense.created', $expense, [], $expense->toArray());

        return redirect()->route('cash.expenses.index')->with('success', __('Expense recorded successfully.'));
    }

    public function edit(Expense $expense)
    {
        return view('cash.expenses.form', [
            'expense' => $expense,
            'categories' => ExpenseCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'payee' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $before = $expense->toArray();
        $expense->update($data);

        app(CashTransactionService::class)->updateSourceAmount($expense, (float) $data['amount']);

        AuditLog::record('expense.updated', $expense, $before, $expense->toArray());

        return redirect()->route('cash.expenses.index')->with('success', __('Expense updated successfully.'));
    }

    public function destroy(Expense $expense)
    {
        app(CashTransactionService::class)->deleteSource($expense);

        AuditLog::record('expense.deleted', $expense, $expense->toArray(), []);
        $expense->delete();

        return redirect()->route('cash.expenses.index')->with('success', __('Expense deleted. The linked cash entry was also removed.'));
    }
}