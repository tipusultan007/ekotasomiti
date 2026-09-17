<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Services\AccountNumberService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankAccountController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(BankAccount::class, 'bankAccount');
    }

    public function index()
    {
        $accounts = BankAccount::withCount('transactions')->orderBy('bank_name')->get();

        return view('bank-accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('bank-accounts.form', ['account' => new BankAccount]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $account = BankAccount::create($data);
        AuditLog::record('bank_account.created', $account, [], $account->toArray());

        return redirect()->route('bank-accounts.index')->with('success', __('Bank account created successfully.'));
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('bank-accounts.form', ['account' => $bankAccount]);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $this->validateData($request);

        $before = $bankAccount->toArray();
        $bankAccount->update($data);
        AuditLog::record('bank_account.updated', $bankAccount, $before, $bankAccount->toArray());

        return redirect()->route('bank-accounts.index')->with('success', __('Bank account updated successfully.'));
    }

    public function destroy(BankAccount $bankAccount)
    {
        if ($bankAccount->transactions()->exists()) {
            return back()->with('error', __('Bank account has transactions and cannot be deleted.'));
        }

        AuditLog::record('bank_account.deleted', $bankAccount, $bankAccount->toArray(), []);
        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')->with('success', __('Bank account deleted.'));
    }

    public function storeTransaction(Request $request, BankAccount $bankAccount)
    {
        $data = $request->validate([
            'type' => 'required|in:deposit,withdrawal,transfer',
            'direction' => 'required|in:in,out',
            'amount' => 'required|numeric|gt:0',
            'txn_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $amount = (float) $data['amount'];
        $newBalance = $data['direction'] === 'in'
            ? (float) $bankAccount->current_balance + $amount
            : (float) $bankAccount->current_balance - $amount;

        if ($newBalance < 0) {
            return back()->with('error', __('Insufficient bank balance for this transaction.'));
        }

        $data['txn_no'] = app(AccountNumberService::class)->nextTransactionNumber('bank_txn', 'BT-', true, $data['txn_date']);
        $data['created_by'] = auth()->id();

        $txn = BankTransaction::create($data);
        $bankAccount->update(['current_balance' => $newBalance]);

        AuditLog::record('bank_transaction.created', $txn, [], $txn->toArray());

        return back()->with('success', __('Bank transaction recorded successfully.'));
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => ['nullable', 'string', 'max:50', Rule::unique('bank_accounts', 'account_number')->ignore($request->route('bankAccount'))],
            'bank_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);
    }
}