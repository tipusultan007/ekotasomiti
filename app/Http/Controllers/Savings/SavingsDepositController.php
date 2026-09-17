<?php

namespace App\Http\Controllers\Savings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Services\SavingsTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SavingsDepositController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('make deposits');

        $query = SavingsTransaction::with(['member', 'account', 'receiver', 'fieldOfficer'])
            ->where('type', 'deposit')
            ->where('status', 'posted');

        if (auth()->user()->isFieldOfficer()) {
            $query->where(function ($q) {
                $q->where('field_officer_id', auth()->id())
                    ->orWhere('received_by', auth()->id());
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('txn_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('txn_date', '<=', $request->input('date_to'));
        }

        $transactions = $query->orderByDesc('txn_date')->paginate(20)->withQueryString();

        $totalTodayQuery = SavingsTransaction::where('type', 'deposit')
            ->where('status', 'posted')
            ->whereDate('txn_date', now()->toDateString());
        if (auth()->user()->isFieldOfficer()) {
            $totalTodayQuery->where(function ($q) {
                $q->where('field_officer_id', auth()->id())
                    ->orWhere('received_by', auth()->id());
            });
        }
        $totalToday = $totalTodayQuery->sum('amount');

        $totalRangeQuery = SavingsTransaction::where('type', 'deposit')
            ->where('status', 'posted')
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('txn_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('txn_date', '<=', $request->input('date_to')));
        if (auth()->user()->isFieldOfficer()) {
            $totalRangeQuery->where(function ($q) {
                $q->where('field_officer_id', auth()->id())
                    ->orWhere('received_by', auth()->id());
            });
        }
        $totalRange = $totalRangeQuery->sum('amount');

        return view('savings.deposits.index', compact('transactions', 'totalToday', 'totalRange'));
    }

    public function create(Request $request)
    {
        $this->authorize('make deposits');

        $query = SavingsAccount::active()->with(['member', 'program']);

        if (auth()->user()->isFieldOfficer()) {
            $query->whereIn('area_id', auth()->user()->officerAreaIds());
        }

        if ($request->filled('account_id')) {
            $query->where('id', $request->input('account_id'));
        }

        $accounts = $query->orderBy('account_no')->get();

        return view('savings.deposits.create', [
            'accounts' => $accounts,
            'selectedAccount' => $request->filled('account_id') ? SavingsAccount::find($request->input('account_id')) : null,
            'defaultDate' => $request->input('date', now()->toDateString()),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('make deposits');

        $data = $request->validate([
            'account_id' => 'required|exists:savings_accounts,id',
            'amount' => 'required|numeric|gt:0',
            'txn_date' => 'required|date',
            'collection_date' => 'nullable|date',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $account = SavingsAccount::active()->find($data['account_id']);

        if (! $account) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('This savings account is not active.'),
                ], 422);
            }
            return back()->withInput()->with('error', __('This savings account is not active.'));
        }

        $this->authorize('view', $account);

        // Concurrency Guard 1: Atomic cache lock
        $lockKey = "savings_deposit_lock_{$account->id}_" . (auth()->id() ?? 'guest');
        $lock = Cache::lock($lockKey, 5);

        if (! $lock->get()) {
            return back()->withInput()->with('warning', __('A deposit for this account is already being recorded. Please wait a moment.'));
        }

        // Concurrency Guard 2: Recent duplicate check within last 4 seconds
        $recentDuplicate = SavingsTransaction::where('savings_account_id', $account->id)
            ->where(function ($q) use ($data) {
                $q->where('gross_amount', $data['amount'])
                    ->orWhere('amount', $data['amount']);
            })
            ->where('txn_date', $data['txn_date'])
            ->where('created_at', '>=', now()->subSeconds(4))
            ->first();

        if ($recentDuplicate) {
            $lock->release();
            return redirect()->route('savings.receipts.show', ['type' => 'savings', 'id' => $recentDuplicate->id])
                ->with('warning', __('Deposit of :amount was already recorded just now. Duplicate submission prevented.', [
                    'amount' => '৳' . number_format($data['amount'], 2),
                ]));
        }

        try {
            $txn = app(SavingsTransactionService::class)->deposit($account, $data);
        } catch (\RuntimeException $e) {
            $lock->release();
            return back()->withInput()->with('error', $e->getMessage());
        }

        AuditLog::record('savings_deposit.created', $txn, [], $txn->toArray());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Deposit recorded successfully. Receipt: :no', ['no' => $txn->txn_no]),
                'receipt_url' => route('savings.receipts.show', ['type' => 'savings', 'id' => $txn->id]),
            ]);
        }

        return redirect()->route('savings.receipts.show', ['type' => 'savings', 'id' => $txn->id])
            ->with('success', __('Deposit recorded successfully. Receipt: :no', ['no' => $txn->txn_no]));
    }
}