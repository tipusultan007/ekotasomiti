<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use Illuminate\Support\Facades\DB;

class SavingsTransactionService
{
    public function __construct(
        protected AccountNumberService $numberService,
        protected CashTransactionService $cashService,
        protected FundService $fundService
    ) {}

    public function deposit(SavingsAccount $account, array $data): SavingsTransaction
    {
        $grossAmount = (float) $data['amount'];

        if ($grossAmount <= 0) {
            throw new \RuntimeException('Deposit amount must be greater than zero.');
        }

        $account->loadMissing('program.fund');
        $fundAmount = 0.0;
        $fund = null;

        if ($account->program && $account->program->fund_id && (float) $account->program->fund_contribution > 0) {
            $contribution = (float) $account->program->fund_contribution;
            if ($grossAmount >= $contribution) {
                $fundAmount = $contribution;
                $fund = $account->program->fund;
            }
        } elseif ($account->program && $account->program->frequency === 'monthly') {
            // Global default for all monthly savings accounts: 50 Tk to global Welfare Fund
            $globalFund = \App\Models\Fund::where('code', 'WF')->orWhere('type', 'welfare')->first();
            if ($globalFund && $grossAmount >= 50.00) {
                $fundAmount = 50.00;
                $fund = $globalFund;
            }
        }

        $netSavingsAmount = $grossAmount - $fundAmount;

        $data['type'] = 'deposit';
        $data['txn_no'] = $data['txn_no'] ?? $this->numberService->nextTransactionNumber(
            'savings_deposit', 'SD-', true, $data['txn_date'] ?? now()->toDateString()
        );
        $data['direction'] = 'credit';
        $data['gross_amount'] = $grossAmount;
        $data['fund_amount'] = $fundAmount;
        $data['amount'] = $netSavingsAmount;
        $data['cash_amount'] = $grossAmount;
        $data['cash_type'] = 'savings_collection';
        $data['cash_notes'] = "Savings deposit {$account->account_no} ({$data['txn_no']})";

        return DB::transaction(function () use ($account, $data, $fund, $fundAmount) {
            $txn = $this->record($account, $data);

            if ($fund && $fundAmount > 0) {
                $fundTxn = $this->fundService->recordContribution($fund, $fundAmount, [
                    'member_id' => $account->member_id,
                    'savings_account_id' => $account->id,
                    'savings_transaction_id' => $txn->id,
                    'txn_date' => $txn->txn_date->toDateString(),
                    'payment_method' => $txn->payment_method,
                    'reference' => $txn->txn_no,
                    'notes' => "Fund contribution from savings deposit {$txn->txn_no} ({$account->account_no})",
                    'received_by' => $txn->received_by,
                    'record_cash' => false,
                ]);

                $txn->update(['fund_transaction_id' => $fundTxn->id]);
            }

            return $txn;
        });
    }

    public function withdraw(SavingsAccount $account, array $data): SavingsTransaction
    {
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new \RuntimeException('Withdrawal amount must be greater than zero.');
        }

        if ((float) $account->current_balance < $amount) {
            throw new \RuntimeException('Insufficient balance for withdrawal.');
        }

        $data['type'] = 'withdrawal';
        $data['txn_no'] = $data['txn_no'] ?? $this->numberService->nextTransactionNumber(
            'savings_withdrawal', 'SW-', true, $data['txn_date'] ?? now()->toDateString()
        );
        $data['direction'] = 'debit';
        $data['cash_type'] = 'withdrawal_payment';
        $data['cash_notes'] = "Savings withdrawal {$account->account_no} ({$data['txn_no']})";

        return DB::transaction(fn () => $this->record($account, $data));
    }

    public function accountOpening(SavingsAccount $account, array $data): SavingsTransaction
    {
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new \RuntimeException('Opening balance must be greater than zero.');
        }

        $data['type'] = 'account_opening';
        $data['txn_no'] = $data['txn_no'] ?? $this->numberService->nextTransactionNumber(
            'savings_opening', 'SO-', true, $data['txn_date'] ?? now()->toDateString()
        );
        $data['direction'] = 'credit';
        $data['cash_type'] = 'savings_collection';
        $data['cash_notes'] = "Savings account opening {$account->account_no} ({$data['txn_no']})";

        return DB::transaction(fn () => $this->record($account, $data));
    }

    public function accountClosing(SavingsAccount $account, array $data): SavingsTransaction
    {
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new \RuntimeException('Closing payout must be greater than zero.');
        }

        if ((float) $account->current_balance < $amount) {
            throw new \RuntimeException('Insufficient balance for account closing.');
        }

        $data['type'] = 'account_closing';
        $data['txn_no'] = $data['txn_no'] ?? $this->numberService->nextTransactionNumber(
            'savings_closing', 'SC-', true, $data['txn_date'] ?? now()->toDateString()
        );
        $data['direction'] = 'debit';
        $data['cash_type'] = 'withdrawal_payment';
        $data['cash_notes'] = "Savings account closing payout {$account->account_no} ({$data['txn_no']})";

        return DB::transaction(fn () => $this->record($account, $data));
    }

    protected function record(SavingsAccount $account, array $data): SavingsTransaction
    {
        $amount = (float) $data['amount'];
        $grossAmount = (float) ($data['gross_amount'] ?? $amount);
        $fundAmount = (float) ($data['fund_amount'] ?? 0);
        $cashAmount = (float) ($data['cash_amount'] ?? $grossAmount);

        $credit = ($data['direction'] ?? 'credit') === 'credit';
        $before = (float) $account->current_balance;
        $after = $credit ? $before + $amount : $before - $amount;

        $txn = SavingsTransaction::create([
            'txn_no' => $data['txn_no'],
            'savings_account_id' => $account->id,
            'member_id' => $account->member_id,
            'savings_program_id' => $account->savings_program_id,
            'type' => $data['type'],
            'amount' => $amount,
            'gross_amount' => $grossAmount,
            'fund_amount' => $fundAmount,
            'fund_transaction_id' => $data['fund_transaction_id'] ?? null,
            'txn_date' => $data['txn_date'] ?? now()->toDateString(),
            'collection_date' => $data['collection_date'] ?? now()->toDateString(),
            'payment_method' => $data['payment_method'] ?? 'cash',
            'field_officer_id' => $data['field_officer_id'] ?? $account->field_officer_id,
            'area_id' => $data['area_id'] ?? $account->area_id,
            'received_by' => $data['received_by'] ?? auth()->id(),
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'balance_after' => $after,
            'status' => 'posted',
        ]);

        $account->current_balance = $after;
        $account->save();

        $this->cashService->record(
            register: $this->cashService->openRegister(),
            type: $data['cash_type'],
            direction: $credit ? 'in' : 'out',
            amount: $cashAmount,
            source: $txn,
            fieldOfficerId: $txn->field_officer_id,
            paymentMethod: $txn->payment_method,
            reference: $txn->txn_no,
            notes: $data['cash_notes'],
        );

        return $txn;
    }

    public function update(SavingsTransaction $txn, array $data): SavingsTransaction
    {
        if ($txn->status !== 'posted') {
            throw new \RuntimeException('Only posted transactions can be updated.');
        }

        $amount = (float) ($data['amount'] ?? $txn->amount);

        DB::transaction(function () use ($txn, $data, $amount) {
            $account = $txn->account;

            $txn->update([
                'amount' => $amount,
                'txn_date' => $data['txn_date'] ?? $txn->txn_date->toDateString(),
                'collection_date' => $data['collection_date'] ?? $txn->collection_date->toDateString(),
                'notes' => $data['notes'] ?? $txn->notes,
            ]);

            $this->cashService->updateSourceAmount($txn, $amount);

            $this->recomputeBalances($account);
        });

        return $txn->refresh();
    }

    public function delete(SavingsTransaction $txn): void
    {
        if ($txn->status !== 'posted') {
            throw new \RuntimeException('Only posted transactions can be deleted.');
        }

        DB::transaction(function () use ($txn) {
            $account = $txn->account;

            if ($txn->fund_transaction_id && $txn->fundTransaction) {
                $this->fundService->reverse($txn->fundTransaction);
            }

            $this->cashService->deleteSource($txn);
            $txn->delete();

            $this->recomputeBalances($account);
        });
    }

    protected function recomputeBalances(SavingsAccount $account): void
    {
        $balance = 0.0;

        $txns = $account->transactions()
            ->where('status', 'posted')
            ->orderBy('txn_date')
            ->orderBy('id')
            ->get();

        foreach ($txns as $t) {
            $credit = in_array($t->type, ['deposit', 'account_opening', 'adjustment']);
            $balance = $credit ? $balance + (float) $t->amount : $balance - (float) $t->amount;
            $t->update(['balance_after' => $balance]);
        }

        $account->current_balance = $balance;
        $account->save();
    }

    public function reverse(SavingsTransaction $txn): SavingsTransaction
    {
        if ($txn->status !== 'posted') {
            throw new \RuntimeException('Only posted transactions can be reversed.');
        }

        $amount = (float) $txn->amount;
        $account = $txn->account;
        $isCredit = $txn->type === 'deposit' || $txn->type === 'account_opening' || $txn->type === 'adjustment';

        return DB::transaction(function () use ($txn, $account, $amount, $isCredit) {
            if ($txn->fund_transaction_id && $txn->fundTransaction) {
                $this->fundService->reverse($txn->fundTransaction);
            }

            $newBalance = $isCredit
                ? (float) $account->current_balance - $amount
                : (float) $account->current_balance + $amount;

            $reversal = SavingsTransaction::create([
                'txn_no' => $this->numberService->nextTransactionNumber('savings_reversal', 'SR-', true),
                'savings_account_id' => $account->id,
                'member_id' => $account->member_id,
                'savings_program_id' => $account->savings_program_id,
                'type' => 'correction',
                'amount' => $amount,
                'txn_date' => now()->toDateString(),
                'collection_date' => now()->toDateString(),
                'payment_method' => $txn->payment_method,
                'field_officer_id' => $txn->field_officer_id,
                'area_id' => $txn->area_id,
                'received_by' => auth()->id(),
                'reference' => $txn->txn_no,
                'notes' => "Reversal of {$txn->txn_no}",
                'balance_after' => $newBalance,
                'status' => 'posted',
                'reversed_txn_id' => $txn->id,
            ]);

            $txn->update(['status' => 'reversed']);
            $account->current_balance = $newBalance;
            $account->save();

            $this->cashService->reverseSource($txn);

            return $reversal;
        });
    }
}
