<?php

namespace App\Services;

use App\Models\Fund;
use App\Models\FundTransaction;
use Illuminate\Support\Facades\DB;

class FundService
{
    public function __construct(
        protected AccountNumberService $numberService,
        protected CashTransactionService $cashService
    ) {}

    public function createFund(array $data): Fund
    {
        return Fund::create([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'type' => $data['type'] ?? 'welfare',
            'description' => $data['description'] ?? null,
            'current_balance' => 0,
            'status' => $data['status'] ?? 'active',
            'created_by' => auth()->id(),
        ]);
    }

    public function recordContribution(Fund $fund, float $amount, array $data): FundTransaction
    {
        if ($amount <= 0) {
            throw new \RuntimeException('Contribution amount must be greater than zero.');
        }

        return DB::transaction(function () use ($fund, $amount, $data) {
            $fund = Fund::where('id', $fund->id)->lockForUpdate()->first();
            $balanceAfter = (float) $fund->current_balance + $amount;

            $txnDate = $data['txn_date'] ?? now()->toDateString();
            $txnNo = $data['txn_no'] ?? $this->numberService->nextTransactionNumber('fund_txn', 'FT-', true, $txnDate);

            $fundTxn = FundTransaction::create([
                'txn_no' => $txnNo,
                'fund_id' => $fund->id,
                'type' => $data['type'] ?? 'contribution',
                'direction' => 'credit',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'member_id' => $data['member_id'] ?? null,
                'savings_account_id' => $data['savings_account_id'] ?? null,
                'savings_transaction_id' => $data['savings_transaction_id'] ?? null,
                'txn_date' => $txnDate,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $data['received_by'] ?? auth()->id(),
                'created_by' => auth()->id(),
                'status' => 'posted',
            ]);

            $fund->current_balance = $balanceAfter;
            $fund->save();

            // If this contribution is direct (not attached to savings deposit), record cash in
            if (empty($data['savings_transaction_id']) && ($data['record_cash'] ?? false)) {
                $this->cashService->record(
                    register: $this->cashService->openRegister(),
                    type: 'receive',
                    direction: 'in',
                    amount: $amount,
                    source: $fundTxn,
                    paymentMethod: $fundTxn->payment_method,
                    reference: $fundTxn->txn_no,
                    notes: "Direct contribution to fund {$fund->name} ({$fundTxn->txn_no})",
                );
            }

            return $fundTxn;
        });
    }

    public function recordDisbursement(Fund $fund, float $amount, array $data): FundTransaction
    {
        if ($amount <= 0) {
            throw new \RuntimeException('Disbursement amount must be greater than zero.');
        }

        return DB::transaction(function () use ($fund, $amount, $data) {
            $fund = Fund::where('id', $fund->id)->lockForUpdate()->first();

            if ((float) $fund->current_balance < $amount) {
                throw new \RuntimeException('Insufficient fund balance for disbursement.');
            }

            $balanceAfter = (float) $fund->current_balance - $amount;
            $txnDate = $data['txn_date'] ?? now()->toDateString();
            $txnNo = $data['txn_no'] ?? $this->numberService->nextTransactionNumber('fund_disburse', 'FD-', true, $txnDate);

            $fundTxn = FundTransaction::create([
                'txn_no' => $txnNo,
                'fund_id' => $fund->id,
                'type' => 'disbursement',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'member_id' => $data['member_id'] ?? null,
                'txn_date' => $txnDate,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $data['received_by'] ?? auth()->id(),
                'created_by' => auth()->id(),
                'status' => 'posted',
            ]);

            $fund->current_balance = $balanceAfter;
            $fund->save();

            // Record cash register outflow
            $this->cashService->record(
                register: $this->cashService->openRegister(),
                type: 'payment',
                direction: 'out',
                amount: $amount,
                source: $fundTxn,
                paymentMethod: $fundTxn->payment_method,
                reference: $fundTxn->txn_no,
                notes: "Disbursement from fund {$fund->name} ({$fundTxn->txn_no}): " . ($data['notes'] ?? ''),
            );

            return $fundTxn;
        });
    }

    public function reverse(FundTransaction $txn): FundTransaction
    {
        if ($txn->status !== 'posted') {
            throw new \RuntimeException('Only posted transactions can be reversed.');
        }

        return DB::transaction(function () use ($txn) {
            $fund = Fund::where('id', $txn->fund_id)->lockForUpdate()->first();
            $isCredit = $txn->direction === 'credit';
            $amount = (float) $txn->amount;

            $newBalance = $isCredit
                ? (float) $fund->current_balance - $amount
                : (float) $fund->current_balance + $amount;

            if ($newBalance < 0) {
                throw new \RuntimeException('Reversal would result in negative fund balance.');
            }

            $reversal = FundTransaction::create([
                'txn_no' => $this->numberService->nextTransactionNumber('fund_reversal', 'FR-', true),
                'fund_id' => $fund->id,
                'type' => 'adjustment',
                'direction' => $isCredit ? 'debit' : 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'member_id' => $txn->member_id,
                'savings_account_id' => $txn->savings_account_id,
                'savings_transaction_id' => $txn->savings_transaction_id,
                'txn_date' => now()->toDateString(),
                'payment_method' => $txn->payment_method,
                'reference' => $txn->txn_no,
                'notes' => "Reversal of {$txn->txn_no}",
                'received_by' => auth()->id(),
                'created_by' => auth()->id(),
                'status' => 'posted',
                'reversed_txn_id' => $txn->id,
            ]);

            $txn->update(['status' => 'reversed']);
            $fund->current_balance = $newBalance;
            $fund->save();

            $this->cashService->reverseSource($txn);

            return $reversal;
        });
    }
}

