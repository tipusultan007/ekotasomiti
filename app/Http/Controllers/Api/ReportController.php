<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoanTransaction;
use App\Models\SavingsTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function collections(Request $request): JsonResponse
    {
        $officer = $request->user();
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $type = $request->input('type', 'both');

        $savingsQuery = SavingsTransaction::with(['account.member', 'account.program', 'member', 'program'])
            ->where('status', 'posted')
            ->whereIn('type', ['deposit', 'account_opening'])
            ->whereDate('txn_date', '>=', $dateFrom)
            ->whereDate('txn_date', '<=', $dateTo)
            ->where(fn ($q) => $q->where('field_officer_id', $officer->id)->orWhere('received_by', $officer->id));

        $loanQuery = LoanTransaction::with(['loan.member', 'loan.product', 'member'])
            ->where('status', 'posted')
            ->where('type', 'repayment')
            ->whereDate('txn_date', '>=', $dateFrom)
            ->whereDate('txn_date', '<=', $dateTo)
            ->where(fn ($q) => $q->where('field_officer_id', $officer->id)->orWhere('received_by', $officer->id));

        $savingsTotal = 0;
        $loanTotal = 0;
        $loanPrincipal = 0;
        $loanInterest = 0;

        $savingsTransactions = collect();
        $loanTransactions = collect();

        if (in_array($type, ['savings', 'both'])) {
            $savingsTransactions = $savingsQuery->orderByDesc('txn_date')->orderByDesc('id')->get();
            $savingsTotal = (float) $savingsTransactions->sum('amount');
        }

        if (in_array($type, ['loan', 'both'])) {
            $loanTransactions = $loanQuery->orderByDesc('txn_date')->orderByDesc('id')->get();
            $loanTotal = (float) $loanTransactions->sum('amount');
            $loanPrincipal = (float) $loanTransactions->sum('principal_paid');
            $loanInterest = (float) $loanTransactions->sum('interest_paid');
        }

        return response()->json([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'summary' => [
                'savings_total' => $savingsTotal,
                'loan_total' => $loanTotal,
                'loan_principal' => $loanPrincipal,
                'loan_interest' => $loanInterest,
                'grand_total' => $savingsTotal + $loanTotal,
            ],
            'savings_transactions' => $savingsTransactions->map(fn ($t) => [
                'id' => $t->id,
                'txn_no' => $t->txn_no,
                'member_name' => $t->member?->name ?? $t->account?->member?->name ?? '',
                'member_no' => $t->member?->member_no ?? $t->account?->member?->member_no ?? '',
                'account_no' => $t->account->account_no ?? '',
                'program' => $t->program?->name ?? $t->account?->program?->name ?? '',
                'amount' => (float) $t->amount,
                'date' => $t->txn_date ? (is_string($t->txn_date) ? $t->txn_date : $t->txn_date->toDateString()) : '',
                'payment_method' => $t->payment_method,
            ]),
            'loan_transactions' => $loanTransactions->map(fn ($t) => [
                'id' => $t->id,
                'txn_no' => $t->txn_no,
                'member_name' => $t->member?->name ?? $t->loan?->member?->name ?? '',
                'member_no' => $t->member?->member_no ?? $t->loan?->member?->member_no ?? '',
                'loan_no' => $t->loan->loan_no ?? '',
                'product' => $t->loan?->product?->name ?? '',
                'amount' => (float) $t->amount,
                'principal_paid' => (float) $t->principal_paid,
                'interest_paid' => (float) $t->interest_paid,
                'date' => $t->txn_date ? (is_string($t->txn_date) ? $t->txn_date : $t->txn_date->toDateString()) : '',
                'payment_method' => $t->payment_method,
            ]),
        ]);
    }
}
