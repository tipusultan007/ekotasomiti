<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingsAccount;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        Loan::refreshOverdueStatus();

        $user = auth()->user();

        if ($user->isFieldOfficer()) {
            return $this->officerDashboard($user);
        }

        return $this->adminDashboard($user);
    }

    protected function adminDashboard($user): \Illuminate\View\View
    {
        $today = now()->toDateString();

        $data = [
            'totalMembers' => Member::count(),
            'activeMembers' => Member::where('status', 'active')->count(),
            'totalSavings' => SavingsAccount::where('status', 'active')->sum('current_balance'),
            'todaySavingsCollection' => \App\Models\SavingsTransaction::whereDate('txn_date', $today)
                ->where('type', 'deposit')->where('status', 'posted')->sum('amount'),
            'totalOutstanding' => Loan::whereIn('status', ['disbursed', 'active', 'overdue'])->sum('outstanding'),
            'todayLoanCollection' => \App\Models\LoanTransaction::whereDate('txn_date', $today)
                ->where('type', 'repayment')->where('status', 'posted')->sum('amount'),
            'todayDisbursement' => \App\Models\LoanTransaction::whereDate('txn_date', $today)
                ->where('type', 'disbursement')->where('status', 'posted')->sum('amount'),
            'totalOverdue' => Loan::where('status', 'overdue')->count(),
            'todayExpense' => \App\Models\Expense::whereDate('expense_date', $today)
                ->where('status', 'posted')->sum('amount'),
            'cashBalance' => $this->currentCashBalance(),
            'activeLoans' => Loan::whereIn('status', ['disbursed', 'active', 'overdue'])->count(),
            'weekLabels' => $this->weekLabels(),
            'weekSavings' => $this->dailyCollection('savings', 7),
            'weekLoans' => $this->dailyCollection('loans', 7),
            'portfolio' => [
                'savings' => SavingsAccount::where('status', 'active')->sum('current_balance'),
                'loans' => Loan::whereIn('status', ['disbursed', 'active', 'overdue'])->sum('outstanding'),
            ],
        ];

        $data['recentTransactions'] = $this->recentTransactions(10);

        return view('dashboard', $data);
    }

    protected function officerDashboard($user): \Illuminate\View\View
    {
        $officer = $user;
        $today = now()->toDateString();
        $areaIds = $officer->officerAreaIds();

        $todayCollections = \App\Models\SavingsTransaction::where('status', 'posted')
            ->where(fn ($q) => $q->where('field_officer_id', $officer->id)->orWhere('received_by', $officer->id))
            ->whereDate('txn_date', $today)->sum('amount');

        $todayLoanCollections = \App\Models\LoanTransaction::where('type', 'repayment')->where('status', 'posted')
            ->where(fn ($q) => $q->where('field_officer_id', $officer->id)->orWhere('received_by', $officer->id))
            ->whereDate('txn_date', $today)->sum('amount');

        $todayTotal = $todayCollections + $todayLoanCollections;

        $expectedToday = \App\Models\SavingsAccount::where('status', 'active')
            ->whereIn('area_id', $areaIds)->sum('expected_deposit');

        $overdueLoans = Loan::whereIn('area_id', $areaIds)
            ->where('status', 'overdue')->with('member')->get();

        $pendingSettlement = \App\Models\FieldOfficerSettlement::where('field_officer_id', $officer->id)
            ->whereIn('status', ['pending', 'submitted'])->latest()->first();

        $collectedTotal = (float) \App\Models\SavingsTransaction::where('field_officer_id', $officer->id)
            ->where('status', 'posted')->whereIn('type', ['deposit', 'account_opening'])->sum('amount')
            + (float) \App\Models\LoanTransaction::where('field_officer_id', $officer->id)
            ->where('type', 'repayment')->where('status', 'posted')->sum('amount');

        $submitted = (float) \App\Models\FieldOfficerSettlement::where('field_officer_id', $officer->id)
            ->where('status', 'received')->sum('cash_submitted');

        return view('dashboard', [
            'isOfficer' => true,
            'officer' => $officer,
            'assignedMembers' => Member::whereIn('area_id', $areaIds)->count(),
            'activeSavingsAccounts' => SavingsAccount::whereIn('area_id', $areaIds)->where('status', 'active')->count(),
            'expectedToday' => $expectedToday,
            'todaySavingsCollection' => $todayCollections,
            'todayLoanCollection' => $todayLoanCollections,
            'todayCollection' => $todayTotal,
            'overdueLoans' => $overdueLoans,
            'pendingSettlement' => $pendingSettlement,
            'cashInHand' => max(0, $collectedTotal - $submitted),
            'weekLabels' => $this->weekLabels(),
            'weekSavings' => $this->dailyCollection('savings', 7, $officer->id),
            'weekLoans' => $this->dailyCollection('loans', 7, $officer->id),
        ]);
    }

    protected function weekLabels(int $days = 7): array
    {
        return collect(range($days - 1, 0))->map(function ($i) {
            return now()->subDays($i)->translatedFormat('d M');
        })->toArray();
    }

    protected function dailyCollection(string $module, int $days = 7, ?int $officerId = null): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $query = $module === 'savings'
            ? \App\Models\SavingsTransaction::whereDate('txn_date', '>=', $start)
                ->where('type', 'deposit')->where('status', 'posted')
            : \App\Models\LoanTransaction::whereDate('txn_date', '>=', $start)
                ->where('type', 'repayment')->where('status', 'posted');

        if ($officerId) {
            $query->where(fn ($q) => $q->where('field_officer_id', $officerId)->orWhere('received_by', $officerId));
        }

        $rows = $query->selectRaw('DATE(txn_date) as d, SUM(amount) as total')
            ->groupBy('d')->pluck('total', 'd');

        return collect(range($days - 1, 0))->map(function ($i) use ($rows) {
            return (float) $rows->get(now()->subDays($i)->toDateString(), 0);
        })->values()->toArray();
    }

    protected function currentCashBalance(): float
    {
        $register = CashRegister::where('status', 'open')->latest()->first();

        if ($register) {
            return (float) $register->opening_balance + (float) $register->total_in - (float) $register->total_out;
        }

        $lastClosed = CashRegister::where('status', 'closed')->latest()->first();

        return $lastClosed ? (float) $lastClosed->closing_balance : 0.0;
    }

    protected function recentTransactions(int $limit = 10): \Illuminate\Support\Collection
    {
        $savingsQuery = \App\Models\SavingsTransaction::with(['member', 'account'])
            ->where('status', 'posted');

        if (auth()->check() && auth()->user()->isFieldOfficer()) {
            $savingsQuery->where(fn ($q) => $q->where('field_officer_id', auth()->id())->orWhere('received_by', auth()->id()));
        }

        $savings = $savingsQuery->latest()
            ->limit($limit)
            ->get()
            ->map(function ($txn) {
                $txn->module = 'Savings';
                $txn->txn_ref = $txn->txn_no;
                $txn->txn_type = ucfirst($txn->type);
                $txn->txn_amount = $txn->amount;
                $txn->txn_date_val = $txn->txn_date;
                $txn->related = $txn->member?->name . ' / ' . $txn->account?->account_no;

                return $txn;
            });

        $loansQuery = \App\Models\LoanTransaction::with(['member', 'loan'])
            ->where('status', 'posted');

        if (auth()->check() && auth()->user()->isFieldOfficer()) {
            $loansQuery->where(fn ($q) => $q->where('field_officer_id', auth()->id())->orWhere('received_by', auth()->id()));
        }

        $loans = $loansQuery->latest()
            ->limit($limit)
            ->get()
            ->map(function ($txn) {
                $txn->module = 'Loan';
                $txn->txn_ref = $txn->txn_no;
                $txn->txn_type = ucfirst($txn->type);
                $txn->txn_amount = $txn->amount;
                $txn->txn_date_val = $txn->txn_date;
                $txn->related = $txn->member?->name . ' / ' . $txn->loan?->loan_no;

                return $txn;
            });

        return $savings->concat($loans)->sortByDesc('created_at')->take($limit)->values();
    }
}