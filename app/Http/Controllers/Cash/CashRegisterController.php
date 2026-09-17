<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\User;
use App\Services\CashTransactionService;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage cash');

        $date = $request->input('date', now()->toDateString());
        $register = CashRegister::with(['transactions.creator', 'transactions.fieldOfficer'])
            ->where('register_date', $date)
            ->first();

        if (! $register) {
            $register = new CashRegister(['register_date' => $date, 'opening_balance' => 0, 'status' => 'closed']);
        }

        $officers = User::officers()->active()->orderBy('name')->get();

        return view('cash.register', compact('register', 'date', 'officers'));
    }

    public function open(Request $request)
    {
        $this->authorize('manage cash');

        $data = $request->validate([
            'register_date' => 'required|date',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        try {
            $register = app(CashTransactionService::class)->openRegister($data['register_date']);
            $register->update(['opening_balance' => $data['opening_balance']]);
        } catch (\RuntimeException $e) {
            return back()->with('error', __($e->getMessage()));
        }

        AuditLog::record('cash_register.opened', $register, [], $register->toArray());

        return back()->with('success', __('Cash register opened.'));
    }

    public function close(Request $request)
    {
        $this->authorize('close cash register');

        $data = $request->validate([
            'register_date' => 'required|date',
            'physical_cash' => 'nullable|numeric|min:0',
        ]);

        $register = CashRegister::where('register_date', $data['register_date'])->first();

        if (! $register) {
            return back()->with('error', __('No register found for this date.'));
        }

        try {
            $register = app(CashTransactionService::class)->closeRegister($register, $data['physical_cash'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', __($e->getMessage()));
        }

        AuditLog::record('cash_register.closed', $register, [], $register->toArray());

        return back()->with('success', __('Cash register closed.'));
    }

    public function storeTransaction(Request $request)
    {
        $this->authorize('manage cash');

        $data = $request->validate([
            'register_date' => 'required|date',
            'type' => 'required|in:receive,payment,other_income,expense,adjustment',
            'direction' => 'required|in:in,out',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'nullable|in:cash,bank,bkash,nagad,other',
            'field_officer_id' => 'nullable|exists:field_officers,id',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $register = app(CashTransactionService::class)->openRegister($data['register_date']);

        $txn = app(CashTransactionService::class)->record(
            register: $register,
            type: $data['type'],
            direction: $data['direction'],
            amount: (float) $data['amount'],
            fieldOfficerId: $data['field_officer_id'] ?? null,
            paymentMethod: $data['payment_method'] ?? 'cash',
            reference: $data['reference'] ?? null,
            notes: $data['notes'] ?? null,
        );

        AuditLog::record('cash_transaction.created', $txn, [], $txn->toArray());

        return back()->with('success', __('Cash transaction :no recorded.', ['no' => $txn->txn_no]));
    }

    public function reverse(CashTransaction $transaction)
    {
        $this->authorize('reverse transactions');

        try {
            app(CashTransactionService::class)->reverse($transaction);
        } catch (\RuntimeException $e) {
            return back()->with('error', __($e->getMessage()));
        }

        AuditLog::record('cash_transaction.reversed', $transaction, [], $transaction->toArray());

        return back()->with('success', __('Cash transaction reversed.'));
    }
}