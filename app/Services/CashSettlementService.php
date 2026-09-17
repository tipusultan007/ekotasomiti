<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\FieldOfficerSettlement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CashSettlementService
{
    public function __construct(
        protected AccountNumberService $numberService,
        protected CashTransactionService $cashService
    ) {}

    public function computeTotals(User $officer, string $date): array
    {
        $base = fn (string $type) => (float) CashTransaction::posted()
            ->where('field_officer_id', $officer->id)
            ->where('direction', 'in')
            ->whereDate('created_at', $date)
            ->where('type', $type)
            ->sum('amount');

        $savings = $base('savings_collection');
        $loans = $base('loan_collection');
        $other = $base('other_income');

        return [
            'savings_collection' => $savings,
            'loan_collection' => $loans,
            'other_collection' => $other,
            'total_collection' => $savings + $loans + $other,
        ];
    }

    public function create(User $officer, array $data): FieldOfficerSettlement
    {
        $date = $data['settlement_date'] ?? now()->toDateString();
        $totals = $this->computeTotals($officer, $date);

        $savings = (float) ($data['savings_collection'] ?? $totals['savings_collection']);
        $loans = (float) ($data['loan_collection'] ?? $totals['loan_collection']);
        $other = (float) ($data['other_collection'] ?? $totals['other_collection']);
        $total = $savings + $loans + $other;
        $submitted = (float) ($data['cash_submitted'] ?? $total);
        $remaining = $total - $submitted;

        return FieldOfficerSettlement::create([
            'settlement_no' => $this->numberService->nextTransactionNumber('settlement', 'STL-', true, $date),
            'field_officer_id' => $officer->id,
            'settlement_date' => $date,
            'savings_collection' => $savings,
            'loan_collection' => $loans,
            'other_collection' => $other,
            'total_collection' => $total,
            'cash_submitted' => $submitted,
            'remaining_cash' => $remaining,
            'status' => 'submitted',
        ]);
    }

    public function receive(FieldOfficerSettlement $settlement): FieldOfficerSettlement
    {
        if ($settlement->status === 'received') {
            throw new \RuntimeException('Settlement already received.');
        }

        return DB::transaction(function () use ($settlement) {
            $cashTxn = $this->cashService->record(
                register: $this->cashService->openRegister($settlement->settlement_date->toDateString()),
                type: 'officer_submission',
                direction: 'in',
                amount: (float) $settlement->cash_submitted,
                source: $settlement,
                fieldOfficerId: $settlement->field_officer_id,
                paymentMethod: 'cash',
                reference: $settlement->settlement_no,
                notes: "Field officer cash submission {$settlement->settlement_no}",
            );

            $settlement->update([
                'cash_transaction_id' => $cashTxn->id,
                'received_by' => auth()->id(),
                'received_at' => now(),
                'status' => 'received',
            ]);

            return $settlement;
        });
    }
}