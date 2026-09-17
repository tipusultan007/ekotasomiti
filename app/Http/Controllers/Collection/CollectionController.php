<?php

namespace App\Http\Controllers\Collection;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Loan;
use App\Models\LoanTransaction;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Services\LoanRepaymentService;
use App\Services\SavingsTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    protected const FREQUENCIES = ['daily', 'weekly', 'monthly'];

    public function savingsSheet(Request $request, string $frequency)
    {
        $this->authorize('make deposits');
        $this->assertFrequency($frequency);

        $date = $request->input('date', now()->toDateString());
        $officer = auth()->user();

        $accounts = $this->savingsQuery($request, $frequency, $date)->get();

        $rows = $accounts->map(fn ($account) => $this->buildSavingsRow($account, $date));

        $totals = [
            'expected' => $rows->sum('expected'),
            'collected' => $rows->sum('collected'),
            'due' => $rows->sum('due'),
            'overdue' => $rows->sum('overdue'),
            'collectedCount' => $rows->where('status', '!=', 'due')->count(),
        ];

        $view = auth()->user()->isFieldOfficer() ? 'collection.officer.savings' : 'collection.savings';

        return view($view, [
            'rows' => $rows,
            'totals' => $totals,
            'date' => $date,
            'frequency' => $frequency,
            'areas' => auth()->user()->isFieldOfficer()
                ? Area::whereIn('id', auth()->user()->officerAreaIds())->orderBy('name')->get()
                : Area::orderBy('name')->get(),
            'officer' => auth()->user(),
            'accountOptions' => $this->savingsAccountOptions($frequency, $request->input('area_id') ? (int) $request->input('area_id') : null),
            'selectedAccount' => $request->filled('account_id') ? SavingsAccount::find($request->input('account_id')) : null,
            'transactions' => $this->savingsTransactions($request, $frequency, $date),
        ]);
    }

    public function savingsDepositForm(Request $request, string $frequency)
    {
        $this->authorize('make deposits');
        $this->assertFrequency($frequency);

        $date = $request->input('date', now()->toDateString());

        return view('collection.savings-deposit', [
            'frequency' => $frequency,
            'date' => $date,
            'officer' => auth()->user(),
            'accountOptions' => $this->savingsAccountOptions($frequency, $request->input('area_id') ? (int) $request->input('area_id') : null),
            'selectedAccount' => $request->filled('account_id') ? SavingsAccount::find($request->input('account_id')) : null,
        ]);
    }

    public function savingsStore(Request $request, string $frequency)
    {
        $this->authorize('make deposits');
        $this->assertFrequency($frequency);

        $data = $request->validate([
            'account_id' => 'required|exists:savings_accounts,id',
            'amount' => 'required|numeric|gt:0',
            'txn_date' => 'required|date',
            'payment_method' => 'nullable|in:cash,bank,bkash,nagad,other',
            'notes' => 'nullable|string|max:255',
        ]);

        $account = SavingsAccount::findOrFail($data['account_id']);
        $officer = auth()->user();

        // Concurrency Guard 1: Atomic cache lock per account and user (5 seconds)
        $lockKey = "savings_collection_lock_{$account->id}_" . (auth()->id() ?? 'guest');
        $lock = Cache::lock($lockKey, 5);

        if (! $lock->get()) {
            return redirect()->back()->with('warning', __('A deposit for this account is already being processed. Please do not click repeatedly.'));
        }

        // Concurrency Guard 2: Recent duplicate check within last 4 seconds
        $recentDuplicate = SavingsTransaction::where('savings_account_id', $account->id)
            ->where('amount', $data['amount'])
            ->where(function ($q) use ($data) {
                $q->where('gross_amount', $data['amount'])
                    ->orWhere('amount', $data['amount']);
            })
            ->where('txn_date', $data['txn_date'])
            ->where('created_at', '>=', now()->subSeconds(4))
            ->first();

        if ($recentDuplicate) {
            $lock->release();
            return redirect()->back()->with('warning', __('Deposit of :amount for account :account was already recorded just now. Duplicate submission prevented.', [
                'amount' => '৳' . number_format($data['amount'], 2),
                'account' => $account->account_no,
            ]));
        }

        $payload = [
            'amount' => $data['amount'],
            'txn_date' => $data['txn_date'],
            'collection_date' => $data['txn_date'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'notes' => $data['notes'] ?? null,
        ];

        if ($officer) {
            $payload['field_officer_id'] = $officer->id;
        }

        try {
            $txn = app(SavingsTransactionService::class)->deposit($account, $payload);
        } catch (\RuntimeException $e) {
            return back()->with('error', __($e->getMessage()));
        } finally {
            $lock->release();
        }

        AuditLog::record('savings_collection.created', $txn, [], $txn->toArray());

        return redirect()->back()->with('success', __('Installment collected for :account. Receipt: :txn', ['account' => $account->account_no, 'txn' => $txn->txn_no]));
    }

    public function savingsTransactionUpdate(Request $request, SavingsTransaction $transaction)
    {
        $this->authorize('reverse transactions');

        if (! in_array($transaction->type, ['deposit', 'account_opening'])) {
            return back()->with('error', __('Only savings collection transactions can be edited here.'));
        }

        $data = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'txn_date' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            app(SavingsTransactionService::class)->update($transaction, $data);
        } catch (\RuntimeException $e) {
            return back()->with('error', __($e->getMessage()));
        }

        AuditLog::record('savings_transaction.edited', $transaction, [], $transaction->toArray());

        return back()->with('success', __('Savings collection :no updated.', ['no' => $transaction->txn_no]));
    }

    public function savingsTransactionDestroy(SavingsTransaction $transaction)
    {
        $this->authorize('reverse transactions');

        if (! in_array($transaction->type, ['deposit', 'account_opening'])) {
            return back()->with('error', __('Only savings collection transactions can be deleted here.'));
        }

        try {
            app(SavingsTransactionService::class)->delete($transaction);
        } catch (\RuntimeException $e) {
            return back()->with('error', __($e->getMessage()));
        }

        AuditLog::record('savings_transaction.deleted', $transaction, $transaction->toArray(), []);

        return back()->with('success', __('Savings collection :no deleted. The account balance and cash entry were restored.', ['no' => $transaction->txn_no]));
    }

    public function savingsDetails(Request $request, string $frequency)
    {
        $this->authorize('make deposits');
        $this->assertFrequency($frequency);

        $data = $request->validate([
            'account_id' => 'required|exists:savings_accounts,id',
            'date' => 'nullable|date',
        ]);

        $date = $data['date'] ?? now()->toDateString();

        $account = SavingsAccount::with(['member', 'program', 'area', 'transactions' => fn ($q) => $q->where('status', 'posted')->whereDate('txn_date', '<=', $date)])
            ->where('status', 'active')
            ->whereHas('program', fn ($q) => $q->where('frequency', $frequency))
            ->findOrFail($data['account_id']);

        $this->authorize('view', $account);

        $row = $this->buildSavingsRow($account, $date);

        return view('collection._savings-account-details', compact('row', 'date'));
    }

    public function loanDetails(Request $request, string $frequency)
    {
        $this->authorize('collect repayments');
        $this->assertFrequency($frequency);

        $data = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'date' => 'nullable|date',
        ]);

        $date = $data['date'] ?? now()->toDateString();

        $loan = Loan::with(['member', 'product', 'area', 'schedules', 'transactions' => fn ($q) => $q->where('type', 'repayment')->where('status', 'posted')->whereDate('txn_date', '<=', $date)])
            ->where('frequency', $frequency)
            ->findOrFail($data['loan_id']);

        $this->authorize('view', $loan);

        $row = $this->buildLoanRow($loan, $date);

        return view('collection._loans-details', compact('row', 'date'));
    }

    public function savingsPrint(Request $request, string $frequency)
    {
        $this->authorize('make deposits');
        $this->assertFrequency($frequency);

        $date = $request->input('date', now()->toDateString());
        $officer = auth()->user();

        $accounts = $this->savingsQuery($request, $frequency, $date)->get();

        $rows = $accounts->map(fn ($account) => $this->buildSavingsRow($account, $date));

        $totals = [
            'expected' => $rows->sum('expected'),
            'collected' => $rows->sum('collected'),
            'due' => $rows->sum('due'),
            'overdue' => 0,
        ];

        return view('collection.savings_print', compact('rows', 'totals', 'date', 'frequency'));
    }

    public function loanSheet(Request $request, string $frequency)
    {
        $this->authorize('collect repayments');
        $this->assertFrequency($frequency);

        $date = $request->input('date', now()->toDateString());
        $officer = auth()->user();

        $loans = $this->loanQuery($request, $frequency, $date)->get();

        $rows = $loans->map(fn ($loan) => $this->buildLoanRow($loan, $date, $frequency));

        $totals = [
            'due_today' => $rows->sum('due_today'),
            'overdue' => $rows->sum('overdue'),
            'collected' => $rows->sum('collected'),
            'total_due' => $rows->sum('total_due'),
        ];

        $view = auth()->user()->isFieldOfficer() ? 'collection.officer.loans' : 'collection.loans';

        return view($view, [
            'rows' => $rows,
            'totals' => $totals,
            'date' => $date,
            'frequency' => $frequency,
            'areas' => auth()->user()->isFieldOfficer()
                ? Area::whereIn('id', auth()->user()->officerAreaIds())->orderBy('name')->get()
                : Area::orderBy('name')->get(),
            'officer' => auth()->user(),
            'loanOptions' => $this->loanOptions($frequency, $date, $request->input('area_id') ? (int) $request->input('area_id') : null),
            'selectedLoan' => $request->filled('loan_id') ? Loan::find($request->input('loan_id')) : null,
            'transactions' => $this->loanTransactions($request, $frequency, $date),
        ]);
    }

    public function loanRepayForm(Request $request, string $frequency)
    {
        $this->authorize('collect repayments');
        $this->assertFrequency($frequency);

        $date = $request->input('date', now()->toDateString());

        return view('collection.loans-repay', [
            'frequency' => $frequency,
            'date' => $date,
            'officer' => auth()->user(),
            'loanOptions' => $this->loanOptions($frequency, $date, $request->input('area_id') ? (int) $request->input('area_id') : null),
            'selectedLoan' => $request->filled('loan_id') ? Loan::find($request->input('loan_id')) : null,
        ]);
    }

    public function loanStore(Request $request, string $frequency)
    {
        $this->authorize('collect repayments');
        $this->assertFrequency($frequency);

        $data = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|gt:0',
            'collection_date' => 'required|date',
            'payment_method' => 'nullable|in:cash,bank,bkash,nagad,other',
            'notes' => 'nullable|string|max:255',
        ]);

        $loan = Loan::findOrFail($data['loan_id']);
        $officer = auth()->user();

        // Concurrency Guard 1: Atomic cache lock per loan and user (5 seconds)
        $lockKey = "loan_collection_lock_{$loan->id}_" . (auth()->id() ?? 'guest');
        $lock = Cache::lock($lockKey, 5);

        if (! $lock->get()) {
            return redirect()->back()->with('warning', __('A repayment for this loan is already being processed. Please do not click repeatedly.'));
        }

        // Concurrency Guard 2: Recent duplicate check within last 4 seconds
        $recentDuplicate = LoanTransaction::where('loan_id', $loan->id)
            ->where('type', 'repayment')
            ->where('amount', $data['amount'])
            ->where('collection_date', $data['collection_date'])
            ->where('created_at', '>=', now()->subSeconds(4))
            ->first();

        if ($recentDuplicate) {
            $lock->release();
            return redirect()->back()->with('warning', __('Repayment of :amount for loan :loan was already recorded just now. Duplicate submission prevented.', [
                'amount' => '৳' . number_format($data['amount'], 2),
                'loan' => $loan->loan_no,
            ]));
        }

        $payload = [
            'amount' => $data['amount'],
            'collection_date' => $data['collection_date'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'notes' => $data['notes'] ?? null,
        ];

        if ($officer) {
            $payload['field_officer_id'] = $officer->id;
        }

        try {
            $txn = app(LoanRepaymentService::class)->collect($loan, $payload);
        } catch (\RuntimeException $e) {
            return back()->with('error', __($e->getMessage()));
        } finally {
            $lock->release();
        }

        AuditLog::record('loan_collection.created', $txn, [], $txn->toArray());

        return redirect()->back()->with('success', __('Installment collected for :loan. Receipt: :txn', ['loan' => $loan->loan_no, 'txn' => $txn->txn_no]));
    }

    public function loanTransactionUpdate(Request $request, LoanTransaction $transaction)
    {
        $this->authorize('reverse transactions');

        if ($transaction->type !== 'repayment') {
            return back()->with('error', __('Only loan repayment transactions can be edited here.'));
        }

        $data = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'collection_date' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $txn = app(LoanRepaymentService::class)->update($transaction, $data);
        } catch (\RuntimeException $e) {
            return back()->with('error', __($e->getMessage()));
        }

        AuditLog::record('loan_repayment.edited', $txn, [], $txn->toArray());

        return back()->with('success', __('Loan repayment :no updated.', ['no' => $txn->txn_no]));
    }

    public function loanTransactionDestroy(LoanTransaction $transaction)
    {
        $this->authorize('reverse transactions');

        if ($transaction->type !== 'repayment') {
            return back()->with('error', __('Only loan repayment transactions can be deleted here.'));
        }

        try {
            app(LoanRepaymentService::class)->delete($transaction);
        } catch (\RuntimeException $e) {
            return back()->with('error', __($e->getMessage()));
        }

        AuditLog::record('loan_repayment.deleted', $transaction, $transaction->toArray(), []);

        return back()->with('success', __('Loan repayment :no deleted. Loan balances and cash entries were restored.', ['no' => $transaction->txn_no]));
    }

    public function loanPrint(Request $request, string $frequency)
    {
        $this->authorize('collect repayments');
        $this->assertFrequency($frequency);

        $date = $request->input('date', now()->toDateString());
        $officer = auth()->user();

        $loans = $this->loanQuery($request, $frequency, $date)->get();

        $rows = $loans->map(fn ($loan) => $this->buildLoanRow($loan, $date));

        $totals = [
            'due_today' => $rows->sum('due_today'),
            'overdue' => $rows->sum('overdue'),
            'collected' => $rows->sum('collected'),
            'total_due' => $rows->sum('total_due'),
        ];

        return view('collection.loans_print', compact('rows', 'totals', 'date', 'frequency'));
    }

    protected function savingsQuery(Request $request, string $frequency, string $date): \Illuminate\Database\Eloquent\Builder
    {
        $officer = auth()->user();

        return SavingsAccount::with(['member', 'program', 'transactions' => fn ($q) => $q->where('status', 'posted')->whereDate('txn_date', '<=', $date)])
            ->where('status', 'active')
            ->whereHas('program', fn ($q) => $q->where('frequency', $frequency))
            ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->whereIn('area_id', auth()->user()->officerAreaIds()))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->input('area_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->whereKey($request->input('account_id')))
            ->orderBy('account_no');
    }

    protected function savingsAccountOptions(string $frequency, ?int $areaId = null): \Illuminate\Database\Eloquent\Collection
    {
        $officer = auth()->user();

        return SavingsAccount::with(['member', 'program'])
            ->where('status', 'active')
            ->whereHas('program', fn ($q) => $q->where('frequency', $frequency))
            ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->whereIn('area_id', auth()->user()->officerAreaIds()))
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->orderBy('account_no')
            ->get();
    }

    protected function loanQuery(Request $request, string $frequency, string $date): \Illuminate\Database\Eloquent\Builder
    {
        $officer = auth()->user();
        $parsedDate = Carbon::parse($date);
        $month = $parsedDate->month;
        $year = $parsedDate->year;

        return Loan::with(['member', 'product', 'schedules', 'transactions' => fn ($q) => $q->where('type', 'repayment')->where('status', 'posted')->whereDate('txn_date', '<=', $date)])
            ->whereIn('status', ['disbursed', 'active', 'overdue'])
            ->where('frequency', $frequency)
            ->whereHas('schedules', function ($q) use ($date, $frequency, $month, $year) {
                $q->whereIn('status', ['due', 'partial', 'overdue']);
                if ($frequency === 'monthly') {
                    $q->where(fn ($sq) => $sq->where('due_date', '<=', $date)
                        ->orWhere(fn ($ssq) => $ssq->whereYear('due_date', $year)->whereMonth('due_date', $month)));
                } else {
                    $q->where('due_date', '<=', $date);
                }
            })
            ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->whereIn('area_id', auth()->user()->officerAreaIds()))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->input('area_id')))
            ->when($request->filled('loan_id'), fn ($q) => $q->whereKey($request->input('loan_id')))
            ->orderBy('loan_no');
    }

    protected function loanOptions(string $frequency, string $date, ?int $areaId = null): \Illuminate\Database\Eloquent\Collection
    {
        $officer = auth()->user();
        $parsedDate = Carbon::parse($date);
        $month = $parsedDate->month;
        $year = $parsedDate->year;

        return Loan::with(['member', 'product'])
            ->whereIn('status', ['disbursed', 'active', 'overdue'])
            ->where('frequency', $frequency)
            ->whereHas('schedules', function ($q) use ($date, $frequency, $month, $year) {
                $q->whereIn('status', ['due', 'partial', 'overdue']);
                if ($frequency === 'monthly') {
                    $q->where(fn ($sq) => $sq->where('due_date', '<=', $date)
                        ->orWhere(fn ($ssq) => $ssq->whereYear('due_date', $year)->whereMonth('due_date', $month)));
                } else {
                    $q->where('due_date', '<=', $date);
                }
            })
            ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->whereIn('area_id', auth()->user()->officerAreaIds()))
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->orderBy('loan_no')
            ->get();
    }

    protected function savingsTransactions(Request $request, string $frequency, string $date): \Illuminate\Database\Eloquent\Collection
    {
        $officer = auth()->user();

        return SavingsTransaction::with(['account.member', 'account.program', 'fieldOfficer', 'receiver'])
            ->where('status', 'posted')
            ->whereIn('type', ['deposit', 'account_opening'])
            ->whereDate('txn_date', $date)
            ->whereHas('account', fn ($q) => $q->whereHas('program', fn ($p) => $p->where('frequency', $frequency)))
            ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->where(fn ($sq) => $sq->where('field_officer_id', auth()->id())->orWhere('received_by', auth()->id())))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->input('area_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('savings_account_id', $request->input('account_id')))
            ->orderByDesc('txn_date')
            ->get();
    }

    protected function loanTransactions(Request $request, string $frequency, string $date): \Illuminate\Database\Eloquent\Collection
    {
        $officer = auth()->user();

        return LoanTransaction::with(['loan.member', 'loan.product', 'fieldOfficer', 'receiver'])
            ->where('status', 'posted')
            ->where('type', 'repayment')
            ->whereDate('txn_date', $date)
            ->whereHas('loan', fn ($q) => $q->where('frequency', $frequency))
            ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->where(fn ($sq) => $sq->where('field_officer_id', auth()->id())->orWhere('received_by', auth()->id())))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->input('area_id')))
            ->when($request->filled('loan_id'), fn ($q) => $q->where('loan_id', $request->input('loan_id')))
            ->orderByDesc('txn_date')
            ->get();
    }

    protected function buildSavingsRow(SavingsAccount $account, string $date): object
    {
        $expected = (float) $account->expected_deposit;

        $txns = $account->transactions;
        $collected = $txns->filter(fn ($t) => $t->txn_date->toDateString() === $date && in_array($t->type, ['deposit', 'account_opening']))->sum('amount');
        $lastBefore = $txns->filter(fn ($t) => $t->txn_date->toDateString() < $date)->sortByDesc('txn_date')->first();
        $previousBalance = $lastBefore ? (float) $lastBefore->balance_after : (float) $account->opening_balance;

        return (object) [
            'account' => $account,
            'expected' => $expected,
            'collected' => $collected,
            'due' => max(0, $expected - $collected),
            'overdue' => 0,
            'previous_balance' => $previousBalance,
            'current_balance' => (float) $account->current_balance,
            'status' => $collected >= $expected ? 'paid' : ($collected > 0 ? 'partial' : 'due'),
        ];
    }

    protected function buildLoanRow(Loan $loan, string $date, string $frequency = 'daily'): object
    {
        $unpaid = fn ($schedule) => $schedule->status !== 'paid';
        $parsedDate = Carbon::parse($date);

        if ($frequency === 'monthly') {
            $dueToday = $loan->schedules
                ->filter(fn ($s) => $unpaid($s) && $s->due_date->year === $parsedDate->year && $s->due_date->month === $parsedDate->month)
                ->sum(fn ($s) => max(0, (float) $s->total - (float) $s->paid));

            $overdue = $loan->schedules
                ->filter(fn ($s) => $unpaid($s) && ($s->due_date->year < $parsedDate->year || ($s->due_date->year === $parsedDate->year && $s->due_date->month < $parsedDate->month)))
                ->sum(fn ($s) => max(0, (float) $s->total - (float) $s->paid));
        } else {
            $dueToday = $loan->schedules
                ->filter(fn ($s) => $unpaid($s) && $s->due_date->toDateString() === $date)
                ->sum(fn ($s) => max(0, (float) $s->total - (float) $s->paid));

            $overdue = $loan->schedules
                ->filter(fn ($s) => $unpaid($s) && $s->due_date->toDateString() < $date)
                ->sum(fn ($s) => max(0, (float) $s->total - (float) $s->paid));
        }

        $collected = $loan->transactions
            ->filter(fn ($t) => $t->txn_date->toDateString() === $date)
            ->sum('amount');

        $totalDue = max(0, $dueToday + $overdue - $collected);
        $status = ($totalDue <= 0 && ($dueToday + $overdue > 0 || $collected > 0))
            ? 'paid'
            : ($collected > 0 ? 'partial' : ($overdue > 0 ? 'overdue' : 'due'));

        return (object) [
            'loan' => $loan,
            'due_today' => $dueToday,
            'overdue' => $overdue,
            'collected' => $collected,
            'total_due' => $totalDue,
            'outstanding' => (float) $loan->outstanding,
            'status' => $status,
        ];
    }

    protected function periodsElapsedBefore(SavingsAccount $account, string $date): int
    {
        $opening = $account->opening_date ? Carbon::parse($account->opening_date) : $account->created_at;
        $target = Carbon::parse($date)->subDay();

        if ($target->lt($opening)) {
            return 0;
        }

        return match ($account->program->frequency ?? 'daily') {
            'weekly' => $opening->diffInWeeks($target),
            'monthly' => $opening->diffInMonths($target),
            default => $opening->diffInDays($target),
        };
    }

    protected function assertFrequency(string $frequency): void
    {
        abort_unless(in_array($frequency, self::FREQUENCIES, true), 404);
    }
}