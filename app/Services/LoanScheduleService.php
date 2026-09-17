<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use Illuminate\Support\Facades\DB;

class LoanScheduleService
{
    public function buildSchedule(Loan $loan): void
    {
        $principal = (float) $loan->principal_amount;
        $term = (int) $loan->term;
        $rate = (float) $loan->interest_rate;

        [$calcInterest, $calcPayable, $calcInstallment] = $this->calculate($loan, $principal, $term, $rate);

        // If installment_amount was manually specified (> 0), use it; otherwise use calculated installment
        if ((float) $loan->installment_amount > 0) {
            $installment = (float) $loan->installment_amount;
            $totalPayable = round($installment * $term, 2);
            $totalInterest = max(0, round($totalPayable - $principal, 2));
        } else {
            $installment = $calcInstallment;
            $totalPayable = $calcPayable;
            $totalInterest = $calcInterest;
        }

        $loan->total_interest = $totalInterest;
        $loan->total_payable = $totalPayable;
        $loan->installment_amount = $installment;
        $loan->outstanding = $totalPayable;
        $loan->save();

        $principalPer = $term > 0 ? round($principal / $term, 2) : 0;
        $interestPer = $term > 0 ? round($totalInterest / $term, 2) : 0;

        $start = $loan->first_due_date?->copy() ?? \Carbon\Carbon::parse($loan->disbursement_date)->addDay();

        for ($i = 1; $i <= $term; $i++) {
            $dueDate = $start->copy();
            if ($loan->frequency === 'daily') {
                $dueDate->addDays($i - 1);
            } elseif ($loan->frequency === 'weekly') {
                $dueDate->addWeeks($i - 1);
            } else {
                $dueDate->addMonths($i - 1);
            }

            LoanSchedule::create([
                'loan_id' => $loan->id,
                'installment_no' => $i,
                'due_date' => $dueDate,
                'principal' => $principalPer,
                'interest' => $interestPer,
                'total' => $installment,
                'paid' => 0,
                'status' => 'due',
            ]);
        }
    }

    public function calculate(Loan $loan, float $principal, int $term, float $rate): array
    {
        if ($loan->interest_type === 'reducing') {
            $periodRate = $this->periodRate($loan, $rate) / 100;
            if ($periodRate > 0) {
                $installment = $principal * $periodRate * pow(1 + $periodRate, $term) / (pow(1 + $periodRate, $term) - 1);
                $totalPayable = $installment * $term;
                $totalInterest = $totalPayable - $principal;

                return [round($totalInterest, 2), round($totalPayable, 2), round($installment, 2)];
            }
        }

        $totalInterest = $principal * $rate / 100;
        $totalPayable = $principal + $totalInterest;
        $installment = $totalPayable / $term;

        return [round($totalInterest, 2), round($totalPayable, 2), round($installment, 2)];
    }

    protected function periodRate(Loan $loan, float $annualRate): float
    {
        return match ($loan->frequency) {
            'daily' => $annualRate / 365,
            'weekly' => $annualRate / 52,
            default => $annualRate / 12,
        };
    }
}
