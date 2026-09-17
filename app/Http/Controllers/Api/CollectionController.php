<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SavingsDepositRequest;
use App\Http\Requests\Api\LoanRepayRequest;
use App\Models\AuditLog;
use App\Models\Area;
use App\Models\Loan;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\LoanTransaction;
use App\Services\LoanRepaymentService;
use App\Services\SavingsTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CollectionController extends Controller
{
    protected const FREQUENCIES = ['daily', 'weekly', 'monthly'];

    public function savingsSheet(Request $request, string $frequency): JsonResponse
    {
        abort_unless(in_array($frequency, self::FREQUENCIES, true), 404);

        $date = $request->input('date', now()->toDateString());
        $officer = $request->user();

        $accounts = SavingsAccount::with(['member:id,member_no,name,mobile', 'program:id,name,code,frequency', 'transactions' => fn ($q) => $q->where('status', 'posted')->whereDate('txn_date', '<=', $date)])
            ->where('status', 'active')
            ->whereHas('program', fn ($q) => $q->where('frequency', $frequency))
            ->whereIn('area_id', $officer->officerAreaIds())
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->input('area_id')))
            ->orderBy('account_no')
            ->get();

        $rows = $accounts->map(fn ($account) => $this->buildSavingsRow($account, $date));

        $totals = [
            'expected' => $rows->sum('expected'),
            'collected' => $rows->sum('collected'),
            'due' => $rows->sum('due'),
            'overdue' => 0,
            'collected_count' => $rows->where('status', '!=', 'due')->count(),
        ];

        $transactions = SavingsTransaction::with(['account.member', 'account.program', 'fieldOfficer', 'receiver'])
            ->where('status', 'posted')
            ->whereIn('type', ['deposit', 'account_opening'])
            ->whereDate('txn_date', $date)
            ->whereHas('account', fn ($q) => $q->whereHas('program', fn ($p) => $p->where('frequency', $frequency)))
            ->where(fn ($sq) => $sq->where('field_officer_id', $officer->id)->orWhere('received_by', $officer->id))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->input('area_id')))
            ->orderByDesc('txn_date')
            ->get();

        return response()->json([
            'rows' => $rows,
            'totals' => $totals,
            'date' => $date,
            'frequency' => $frequency,
            'transactions' => $transactions,
        ]);
    }

    public function loanSheet(Request $request, string $frequency): JsonResponse
    {
        abort_unless(in_array($frequency, self::FREQUENCIES, true), 404);

        $date = $request->input('date', now()->toDateString());
        $officer = $request->user();
        $parsedDate = Carbon::parse($date);
        $month = $parsedDate->month;
        $year = $parsedDate->year;

        $loans = Loan::with(['member:id,member_no,name,mobile', 'product:id,name,code,frequency', 'schedules', 'transactions' => fn ($q) => $q->where('type', 'repayment')->where('status', 'posted')->whereDate('txn_date', '<=', $date)])
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
            ->whereIn('area_id', $officer->officerAreaIds())
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->input('area_id')))
            ->orderBy('loan_no')
            ->get();

        $rows = $loans->map(fn ($loan) => $this->buildLoanRow($loan, $date, $frequency));

        $totals = [
            'due_today' => $rows->sum('due_today'),
            'overdue' => $rows->sum('overdue'),
            'collected' => $rows->sum('collected'),
            'total_due' => $rows->sum('total_due'),
        ];

        $transactions = LoanTransaction::with(['loan.member', 'loan.product', 'fieldOfficer', 'receiver'])
            ->where('status', 'posted')
            ->where('type', 'repayment')
            ->whereDate('txn_date', $date)
            ->whereHas('loan', fn ($q) => $q->where('frequency', $frequency))
            ->where(fn ($sq) => $sq->where('field_officer_id', $officer->id)->orWhere('received_by', $officer->id))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->input('area_id')))
            ->orderByDesc('txn_date')
            ->get();

        return response()->json([
            'rows' => $rows,
            'totals' => $totals,
            'date' => $date,
            'frequency' => $frequency,
            'transactions' => $transactions,
        ]);
    }

    public function savingsDeposit(SavingsDepositRequest $request): JsonResponse
    {
        $data = $request->validated();

        $account = SavingsAccount::findOrFail($data['account_id']);
        $officer = $request->user();

        abort_unless(in_array($account->area_id, $officer->officerAreaIds()), 403);

        $payload = [
            'amount' => $data['amount'],
            'txn_date' => $data['txn_date'],
            'collection_date' => $data['txn_date'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'notes' => $data['notes'] ?? null,
            'field_officer_id' => $officer->id,
        ];

        try {
            $txn = app(SavingsTransactionService::class)->deposit($account, $payload);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => __($e->getMessage())], 422);
        }

        AuditLog::record('savings_collection.created', $txn, [], $txn->toArray());

        return response()->json([
            'message' => "Installment collected for {$account->account_no}. Receipt: {$txn->txn_no}",
            'transaction' => [
                'id' => $txn->id,
                'txn_no' => $txn->txn_no,
                'amount' => $txn->amount,
                'balance_after' => $txn->balance_after,
            ],
        ]);
    }

    public function loanRepay(LoanRepayRequest $request): JsonResponse
    {
        $data = $request->validated();

        $loan = Loan::findOrFail($data['loan_id']);
        $officer = $request->user();

        abort_unless(in_array($loan->area_id, $officer->officerAreaIds()), 403);

        $payload = [
            'amount' => $data['amount'],
            'collection_date' => $data['collection_date'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'notes' => $data['notes'] ?? null,
            'field_officer_id' => $officer->id,
        ];

        try {
            $txn = app(LoanRepaymentService::class)->collect($loan, $payload);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => __($e->getMessage())], 422);
        }

        AuditLog::record('loan_collection.created', $txn, [], $txn->toArray());

        return response()->json([
            'message' => "Installment collected for {$loan->loan_no}. Receipt: {$txn->txn_no}",
            'transaction' => [
                'id' => $txn->id,
                'txn_no' => $txn->txn_no,
                'amount' => $txn->amount,
                'principal_paid' => $txn->principal_paid,
                'interest_paid' => $txn->interest_paid,
            ],
        ]);
    }

    public function syncBatch(Request $request): JsonResponse
    {
        $officer = $request->user();
        $officerAreaIds = $officer->officerAreaIds();

        $request->validate([
            'collections' => 'required|array|min:1',
            'collections.*.type' => 'required|in:savings,loan',
            'collections.*.amount' => 'required|numeric|min:1',
            'collections.*.account_id' => 'required_if:collections.*.type,savings|nullable|integer',
            'collections.*.loan_id' => 'required_if:collections.*.type,loan|nullable|integer',
            'collections.*.txn_date' => 'nullable|date',
            'collections.*.collection_date' => 'nullable|date',
            'collections.*.payment_method' => 'nullable|string',
            'collections.*.notes' => 'nullable|string',
            'collections.*.local_id' => 'nullable|string',
        ]);

        $collections = $request->input('collections', []);
        $results = [];
        $syncedCount = 0;
        $failedCount = 0;

        foreach ($collections as $index => $item) {
            $localId = $item['local_id'] ?? "item_{$index}";
            $type = $item['type'];
            $amount = (float) $item['amount'];
            $date = $item['txn_date'] ?? $item['collection_date'] ?? now()->toDateString();
            $paymentMethod = $item['payment_method'] ?? 'cash';
            $notes = $item['notes'] ?? null;

            try {
                if ($type === 'savings') {
                    $account = SavingsAccount::find($item['account_id'] ?? null);
                    if (! $account) {
                        throw new \RuntimeException("Savings account #{$item['account_id']} not found.");
                    }
                    if (! in_array($account->area_id, $officerAreaIds, true)) {
                        throw new \RuntimeException("Account {$account->account_no} is outside your assigned area.");
                    }

                    $payload = [
                        'amount' => $amount,
                        'txn_date' => $date,
                        'collection_date' => $date,
                        'payment_method' => $paymentMethod,
                        'notes' => $notes,
                        'field_officer_id' => $officer->id,
                    ];

                    $txn = app(SavingsTransactionService::class)->deposit($account, $payload);
                    AuditLog::record('savings_collection.created', $txn, [], $txn->toArray());

                    $results[] = [
                        'local_id' => $localId,
                        'status' => 'success',
                        'type' => 'savings',
                        'id' => $txn->id,
                        'txn_no' => $txn->txn_no,
                        'account_no' => $account->account_no,
                        'amount' => (float) $txn->amount,
                        'balance_after' => (float) $txn->balance_after,
                    ];
                    $syncedCount++;
                } elseif ($type === 'loan') {
                    $loan = Loan::find($item['loan_id'] ?? null);
                    if (! $loan) {
                        throw new \RuntimeException("Loan #{$item['loan_id']} not found.");
                    }
                    if (! in_array($loan->area_id, $officerAreaIds, true)) {
                        throw new \RuntimeException("Loan {$loan->loan_no} is outside your assigned area.");
                    }

                    $payload = [
                        'amount' => $amount,
                        'collection_date' => $date,
                        'payment_method' => $paymentMethod,
                        'notes' => $notes,
                        'field_officer_id' => $officer->id,
                    ];

                    $txn = app(LoanRepaymentService::class)->collect($loan, $payload);
                    AuditLog::record('loan_collection.created', $txn, [], $txn->toArray());

                    $results[] = [
                        'local_id' => $localId,
                        'status' => 'success',
                        'type' => 'loan',
                        'id' => $txn->id,
                        'txn_no' => $txn->txn_no,
                        'loan_no' => $loan->loan_no,
                        'amount' => (float) $txn->amount,
                        'principal_paid' => (float) $txn->principal_paid,
                        'interest_paid' => (float) $txn->interest_paid,
                    ];
                    $syncedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
                $results[] = [
                    'local_id' => $localId,
                    'status' => 'failed',
                    'type' => $type,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => "Batch sync completed: {$syncedCount} succeeded, {$failedCount} failed.",
            'synced_count' => $syncedCount,
            'failed_count' => $failedCount,
            'results' => $results,
        ]);
    }

    protected function buildSavingsRow(SavingsAccount $account, string $date): object
    {
        $expected = (float) $account->expected_deposit;
        $txns = $account->transactions;
        $collected = $txns->filter(fn ($t) => $t->txn_date->toDateString() === $date && in_array($t->type, ['deposit', 'account_opening']))->sum('amount');
        $lastBefore = $txns->filter(fn ($t) => $t->txn_date->toDateString() < $date)->sortByDesc('txn_date')->first();
        $previousBalance = $lastBefore ? (float) $lastBefore->balance_after : (float) $account->opening_balance;

        return (object) [
            'account_id' => $account->id,
            'account_no' => $account->account_no,
            'member_name' => $account->member->name ?? '',
            'member_no' => $account->member->member_no ?? '',
            'mobile' => $account->member->mobile ?? '',
            'phone' => $account->member->mobile ?? '',
            'program_name' => $account->program->name ?? '',
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
            'loan_id' => $loan->id,
            'loan_no' => $loan->loan_no,
            'member_name' => $loan->member->name ?? '',
            'member_no' => $loan->member->member_no ?? '',
            'mobile' => $loan->member->mobile ?? '',
            'phone' => $loan->member->mobile ?? '',
            'product_name' => $loan->product->name ?? '',
            'installment_amount' => (float) $loan->installment_amount,
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
}
