<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\LoanTransaction;
use App\Models\FieldOfficerSettlement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Loan::refreshOverdueStatus();

        $officer = $request->user();
        $today = now()->toDateString();
        $areaIds = $officer->officerAreaIds();

        $todaySavingsCollection = (float) SavingsTransaction::where('status', 'posted')
            ->where(fn ($q) => $q->where('field_officer_id', $officer->id)->orWhere('received_by', $officer->id))
            ->whereDate('txn_date', $today)->where('type', 'deposit')->sum('amount');

        $todayLoanCollection = (float) LoanTransaction::where('type', 'repayment')->where('status', 'posted')
            ->where(fn ($q) => $q->where('field_officer_id', $officer->id)->orWhere('received_by', $officer->id))
            ->whereDate('txn_date', $today)->sum('amount');

        $expectedToday = (float) SavingsAccount::where('status', 'active')
            ->whereIn('area_id', $areaIds)->sum('expected_deposit');

        $overdueLoans = Loan::whereIn('area_id', $areaIds)
            ->where('status', 'overdue')->with('member:id,member_no,name,mobile')->count();

        $pendingSettlement = FieldOfficerSettlement::where('field_officer_id', $officer->id)
            ->whereIn('status', ['pending', 'submitted'])->latest()->first();

        $collectedTotal = (float) SavingsTransaction::where('field_officer_id', $officer->id)
            ->where('status', 'posted')->whereIn('type', ['deposit', 'account_opening'])->sum('amount')
            + (float) LoanTransaction::where('field_officer_id', $officer->id)
            ->where('type', 'repayment')->where('status', 'posted')->sum('amount');

        $submitted = (float) FieldOfficerSettlement::where('field_officer_id', $officer->id)
            ->where('status', 'received')->sum('cash_submitted');

        return response()->json([
            'assigned_members' => Member::whereIn('area_id', $areaIds)->count(),
            'active_savings_accounts' => SavingsAccount::whereIn('area_id', $areaIds)->where('status', 'active')->count(),
            'expected_today' => $expectedToday,
            'today_savings_collection' => $todaySavingsCollection,
            'today_loan_collection' => $todayLoanCollection,
            'today_collection' => $todaySavingsCollection + $todayLoanCollection,
            'overdue_loans' => $overdueLoans,
            'pending_settlement' => $pendingSettlement ? [
                'id' => $pendingSettlement->id,
                'settlement_no' => $pendingSettlement->settlement_no,
                'status' => $pendingSettlement->status,
            ] : null,
            'cash_in_hand' => max(0, $collectedTotal - $submitted),
            'week_labels' => $this->weekLabels(),
            'week_savings' => $this->dailyCollection('savings', 7, $officer->id),
            'week_loans' => $this->dailyCollection('loans', 7, $officer->id),
        ]);
    }

    protected function weekLabels(int $days = 7): array
    {
        return collect(range($days - 1, 0))->map(fn ($i) => now()->subDays($i)->format('d M'))->toArray();
    }

    protected function dailyCollection(string $module, int $days = 7, ?int $officerId = null): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $query = $module === 'savings'
            ? SavingsTransaction::whereDate('txn_date', '>=', $start)
                ->where('type', 'deposit')->where('status', 'posted')
            : LoanTransaction::whereDate('txn_date', '>=', $start)
                ->where('type', 'repayment')->where('status', 'posted');

        if ($officerId) {
            $query->where(fn ($q) => $q->where('field_officer_id', $officerId)->orWhere('received_by', $officerId));
        }

        $rows = $query->selectRaw('DATE(txn_date) as d, SUM(amount) as total')
            ->groupBy('d')->pluck('total', 'd');

        return collect(range($days - 1, 0))->map(fn ($i) => (float) $rows->get(now()->subDays($i)->toDateString(), 0))->values()->toArray();
    }
}
