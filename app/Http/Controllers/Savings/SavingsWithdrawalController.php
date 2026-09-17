<?php

namespace App\Http\Controllers\Savings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Services\SavingsTransactionService;
use Illuminate\Http\Request;

class SavingsWithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $query = SavingsTransaction::with(['member', 'account.program', 'fieldOfficer', 'receiver'])
            ->where('type', 'withdrawal')
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

        $totalTodayQuery = SavingsTransaction::where('type', 'withdrawal')
            ->where('status', 'posted')
            ->whereDate('txn_date', now()->toDateString());
        if (auth()->user()->isFieldOfficer()) {
            $totalTodayQuery->where(function ($q) {
                $q->where('field_officer_id', auth()->id())
                    ->orWhere('received_by', auth()->id());
            });
        }
        $totalToday = $totalTodayQuery->sum('amount');

        $totalRangeQuery = SavingsTransaction::where('type', 'withdrawal')
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

        return view('savings.withdrawals.index', compact('transactions', 'totalToday', 'totalRange'));
    }

    public function create(Request $request)
    {
        $accounts = SavingsAccount::active()->with(['member', 'program']);

        if (auth()->user()->isFieldOfficer()) {
            $accounts->whereIn('area_id', auth()->user()->officerAreaIds());
        }

        $accounts = $accounts->orderBy('account_no')->get();

        return view('savings.withdrawals.create', [
            'accounts' => $accounts,
            'selectedAccount' => $request->filled('account_id') ? SavingsAccount::find($request->input('account_id')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'savings_account_id' => 'required|exists:savings_accounts,id',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'purpose' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        $account = SavingsAccount::active()->find($data['savings_account_id']);

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

        try {
            $txn = app(SavingsTransactionService::class)->withdraw($account, [
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'txn_date' => now()->toDateString(),
                'notes' => $data['remarks'] ?? $data['purpose'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }

        AuditLog::record('withdrawal.posted', $txn, [], $txn->toArray());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Withdrawal recorded successfully.'),
            ]);
        }

        return redirect()->route('savings.receipts.show', ['type' => 'withdrawal', 'id' => $txn->id])
            ->with('success', __('Withdrawal recorded successfully. Receipt: :no', ['no' => $txn->txn_no]));
    }
}