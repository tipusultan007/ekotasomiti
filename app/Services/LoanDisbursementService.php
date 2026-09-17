<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanDisbursement;
use App\Models\LoanTransaction;
use Illuminate\Support\Facades\DB;

class LoanDisbursementService
{
    public function __construct(
        protected AccountNumberService $numberService,
        protected LoanScheduleService $scheduleService,
        protected CashTransactionService $cashService
    ) {}

    public function disburse(LoanApplication $application, array $data): Loan
    {
        $amount = (float) ($data['amount'] ?? $application->approved_amount ?? $application->requested_amount);
        $rate = (float) ($data['interest_rate'] ?? $application->approved_interest_rate ?? $application->product->interest_rate);
        $term = (int) ($data['term'] ?? $application->approved_term ?? $application->requested_term);

        return DB::transaction(function () use ($application, $data, $amount, $rate, $term) {
            if ($application->status === 'disbursed' && $application->loan) {
                throw new \RuntimeException('This application has already been disbursed.');
            }

            if ($application->status !== 'approved') {
                throw new \RuntimeException('Application must be ready (approved) before disbursement.');
            }

            $product = $application->product;
            $loanNo = $this->numberService->nextAccountNumber($product->prefix, $application->area?->code);

            $loan = Loan::create([
                'loan_no' => $loanNo,
                'application_id' => $application->id,
                'member_id' => $application->member_id,
                'loan_product_id' => $product->id,
                'area_id' => $application->area_id,
                'field_officer_id' => $application->field_officer_id,
                'principal_amount' => $amount,
                'interest_rate' => $rate,
                'interest_type' => $product->interest_type,
                'term' => $term,
                'frequency' => $product->frequency,
                'installment_amount' => (float) ($data['installment_amount'] ?? $application->installment_amount ?? $application->approved_installment ?? 0),
                'processing_fee' => $product->processing_fee,
                'insurance_fee' => $product->insurance_fee,
                'total_interest' => 0,
                'total_payable' => 0,
                'total_paid' => 0,
                'outstanding' => 0,
                'disbursement_date' => $data['disbursement_date'] ?? now()->toDateString(),
                'first_due_date' => $data['first_due_date'] ?? \Carbon\Carbon::parse($data['disbursement_date'] ?? now())->addDay()->toDateString(),
                'status' => 'disbursed',
                'disbursed_by' => auth()->id(),
                'disbursed_at' => now(),
            ]);

            $this->scheduleService->buildSchedule($loan);

            LoanDisbursement::create([
                'disbursement_no' => $this->numberService->nextTransactionNumber('loan_disbursement', 'LD-', true),
                'loan_id' => $loan->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'disbursed_by' => auth()->id(),
                'disbursed_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            LoanTransaction::create([
                'txn_no' => $this->numberService->nextTransactionNumber('loan_disbursement_txn', 'LDT-', true),
                'loan_id' => $loan->id,
                'member_id' => $loan->member_id,
                'type' => 'disbursement',
                'amount' => $amount,
                'principal_paid' => 0,
                'interest_paid' => 0,
                'late_fee' => 0,
                'txn_date' => $data['disbursement_date'] ?? now()->toDateString(),
                'collection_date' => $data['disbursement_date'] ?? now()->toDateString(),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'field_officer_id' => $loan->field_officer_id,
                'area_id' => $loan->area_id,
                'received_by' => auth()->id(),
                'reference' => $loan->loan_no,
                'notes' => 'Loan disbursement ' . $loan->loan_no,
                'status' => 'posted',
            ]);

            $this->cashService->record(
                register: $this->cashService->openRegister(),
                type: 'loan_disbursement',
                direction: 'out',
                amount: $amount,
                source: $loan,
                fieldOfficerId: $loan->field_officer_id,
                paymentMethod: $data['payment_method'] ?? 'cash',
                reference: $loan->loan_no,
                notes: 'Loan disbursement ' . $loan->loan_no,
            );

            $application->update(['status' => 'disbursed']);

            return $loan;
        });
    }
}