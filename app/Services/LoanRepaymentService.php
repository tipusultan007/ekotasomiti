<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanSchedule;
use App\Models\LoanTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LoanRepaymentService
{
    public function __construct(
        protected AccountNumberService $numberService,
        protected CashTransactionService $cashService
    ) {}

    public function collect(Loan $loan, array $data): LoanTransaction
    {
        $amount = (float) $data['amount'];
        $paymentMethod = $data['payment_method'] ?? 'cash';

        if ($amount <= 0) {
            throw new \RuntimeException('Repayment amount must be greater than zero.');
        }

        if (! in_array($loan->status, ['disbursed', 'active', 'overdue'])) {
            throw new \RuntimeException('Loan is not in an active collectable state.');
        }

        return DB::transaction(function () use ($loan, $amount, $data, $paymentMethod) {
            $date = $data['collection_date'] ?? now()->toDateString();

            $txn = LoanTransaction::create([
                'txn_no' => $this->numberService->nextTransactionNumber('loan_collection', 'LC-', true, $date),
                'loan_id' => $loan->id,
                'member_id' => $loan->member_id,
                'type' => 'repayment',
                'amount' => $amount,
                'principal_paid' => 0,
                'interest_paid' => 0,
                'late_fee' => 0,
                'txn_date' => $date,
                'collection_date' => $date,
                'payment_method' => $paymentMethod,
                'field_officer_id' => $data['field_officer_id'] ?? $loan->field_officer_id,
                'area_id' => $data['area_id'] ?? $loan->area_id,
                'received_by' => auth()->id(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'posted',
            ]);

            $this->allocate($loan, $txn, $amount, $date, $paymentMethod);

            $this->updateLoanTotals($loan);

            $this->cashService->record(
                register: $this->cashService->openRegister(),
                type: 'loan_collection',
                direction: 'in',
                amount: $amount,
                source: $txn,
                fieldOfficerId: $txn->field_officer_id,
                paymentMethod: $paymentMethod,
                reference: $txn->txn_no,
                notes: "Loan repayment {$loan->loan_no} ({$txn->txn_no})",
            );

            return $txn;
        });
    }

    public function update(LoanTransaction $transaction, array $data): LoanTransaction
    {
        if ($transaction->status !== 'posted' || $transaction->type !== 'repayment') {
            throw new \RuntimeException('Only posted repayment transactions can be updated.');
        }

        $amount = (float) ($data['amount'] ?? $transaction->amount);

        return DB::transaction(function () use ($transaction, $data, $amount) {
            $loan = $transaction->loan;
            $date = $data['collection_date'] ?? $transaction->collection_date?->toDateString() ?? now()->toDateString();

            $this->undoAllocation($transaction);

            $transaction->update([
                'amount' => $amount,
                'principal_paid' => 0,
                'interest_paid' => 0,
                'late_fee' => 0,
                'txn_date' => $date,
                'collection_date' => $date,
                'notes' => $data['notes'] ?? $transaction->notes,
            ]);

            $this->allocate($loan, $transaction, $amount, $date, $transaction->payment_method);

            $this->cashService->updateSourceAmount($transaction, $amount);

            $this->updateLoanTotals($loan);

            return $transaction->refresh();
        });
    }

    public function delete(LoanTransaction $transaction): void
    {
        if ($transaction->status !== 'posted' || $transaction->type !== 'repayment') {
            throw new \RuntimeException('Only posted repayment transactions can be deleted.');
        }

        DB::transaction(function () use ($transaction) {
            $loan = $transaction->loan;

            $this->undoAllocation($transaction);
            $transaction->delete();

            $this->cashService->deleteSource($transaction);

            $this->updateLoanTotals($loan);
        });
    }

    public function reverse(LoanTransaction $transaction): void
    {
        if ($transaction->status !== 'posted' || $transaction->type !== 'repayment') {
            throw new \RuntimeException('Only posted repayment transactions can be reversed.');
        }

        DB::transaction(function () use ($transaction) {
            $loan = $transaction->loan;

            $this->undoAllocation($transaction);
            $transaction->update(['status' => 'reversed']);

            $this->updateLoanTotals($loan);

            $this->cashService->reverseSource($transaction);
        });
    }

    protected function undoAllocation(LoanTransaction $transaction): void
    {
        $repayment = LoanRepayment::where('transaction_id', $transaction->id)->first();

        if ($repayment) {
            foreach ($repayment->schedules as $schedule) {
                $schedule->paid = max(0, (float) $schedule->paid - (float) $schedule->pivot->principal_paid - (float) $schedule->pivot->interest_paid - (float) $schedule->pivot->late_fee);
                $schedule->status = $schedule->paid <= 0 ? 'due' : 'partial';
                $schedule->paid_at = $schedule->paid <= 0 ? null : $schedule->paid_at;
                $schedule->save();
            }

            $repayment->delete();
        }
    }

    protected function allocate(Loan $loan, LoanTransaction $txn, float $amount, string $date, string $paymentMethod): void
    {
        $schedules = $this->unpaidSchedules($loan);

        if ($schedules->isEmpty()) {
            throw new \RuntimeException('Loan has no unpaid installments.');
        }

        $remaining = $amount;
        $principalPaid = 0.0;
        $interestPaid = 0.0;
        $lateFeePaid = 0.0;

        $repayment = LoanRepayment::create([
            'loan_id' => $loan->id,
            'transaction_id' => $txn->id,
            'amount' => $amount,
            'principal_paid' => 0,
            'interest_paid' => 0,
            'late_fee' => 0,
            'collection_date' => $date,
        ]);

        foreach ($schedules as $schedule) {
            if ($remaining <= 0) {
                break;
            }

            $due = (float) $schedule->total - (float) $schedule->paid;
            $dueInterest = max(0, (float) $schedule->interest - $this->interestPaidFor($schedule));
            $allocation = min($remaining, $due);

            $allocInterest = min($allocation, $dueInterest);
            $allocPrincipal = $allocation - $allocInterest;

            $schedule->paid = (float) $schedule->paid + $allocation;
            $remaining -= $allocation;
            $principalPaid += $allocPrincipal;
            $interestPaid += $allocInterest;

            $schedule->status = $schedule->paid >= (float) $schedule->total
                ? 'paid'
                : (($schedule->paid > 0) ? 'partial' : 'due');
            $schedule->paid_at = $schedule->paid >= (float) $schedule->total ? now() : $schedule->paid_at;
            $schedule->payment_method = $paymentMethod;
            $schedule->save();

            $repayment->schedules()->attach($schedule->id, [
                'principal_paid' => $allocPrincipal,
                'interest_paid' => $allocInterest,
                'late_fee' => 0,
            ]);
        }

        $repayment->update([
            'principal_paid' => $principalPaid,
            'interest_paid' => $interestPaid,
            'late_fee' => $lateFeePaid,
        ]);

        $txn->update([
            'principal_paid' => $principalPaid,
            'interest_paid' => $interestPaid,
            'late_fee' => $lateFeePaid,
        ]);
    }

    protected function unpaidSchedules(Loan $loan): Collection
    {
        return $loan->schedules()
            ->whereIn('status', ['due', 'partial', 'overdue'])
            ->orderBy('installment_no')
            ->get();
    }

    protected function interestPaidFor(LoanSchedule $schedule): float
    {
        return (float) $schedule->loanRepayments()->sum('loan_repayment_schedule.interest_paid');
    }

    public function updateLoanTotals(Loan $loan): void
    {
        $paid = $loan->schedules()->sum('paid');
        $outstanding = (float) $loan->total_payable - (float) $paid;

        $hasUnpaid = $loan->schedules()->whereIn('status', ['due', 'partial', 'overdue'])->exists();
        $isOverdue = $loan->schedules()
            ->whereIn('status', ['due', 'partial'])
            ->where('due_date', '<', now()->toDateString())
            ->exists();

        $loan->total_paid = $paid;
        $loan->outstanding = $outstanding;
        $loan->status = match (true) {
            $outstanding <= 0 => 'completed',
            $isOverdue => 'overdue',
            default => 'active',
        };

        if ($outstanding <= 0) {
            $loan->closed_at = now();
            $loan->closed_by = auth()->id();
            $loan->closing_reason = 'Loan fully repaid';
        }

        $loan->save();
    }
}