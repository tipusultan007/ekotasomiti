<?php

namespace App\Http\Controllers\Fund;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Fund;
use App\Models\FundTransaction;
use App\Models\Member;
use App\Services\FundService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FundController extends Controller
{
    public function __construct(
        protected FundService $fundService
    ) {
        $this->authorizeResource(Fund::class, 'fund');
    }

    public function index()
    {
        $funds = Fund::withCount(['transactions', 'savingsPrograms'])
            ->withSum(['transactions as total_contributions' => function ($q) {
                $q->where('direction', 'credit')->where('status', 'posted');
            }], 'amount')
            ->withSum(['transactions as total_disbursements' => function ($q) {
                $q->where('direction', 'debit')->where('status', 'posted');
            }], 'amount')
            ->orderBy('name')
            ->get();

        $members = Member::active()->orderBy('name')->get(['id', 'name', 'member_no']);

        return view('funds.index', compact('funds', 'members'));
    }

    public function disburseCreate(Request $request)
    {
        $funds = Fund::active()->orderBy('name')->get();
        $selectedFundId = $request->input('fund_id')
            ? (int) $request->input('fund_id')
            : (Fund::where('code', 'WF')->value('id') ?? $funds->first()?->id);
        $members = Member::active()->orderBy('name')->get(['id', 'name', 'member_no']);

        return view('funds.disburse', compact('funds', 'selectedFundId', 'members'));
    }

    public function globalDisburse(Request $request)
    {
        $data = $request->validate([
            'fund_id' => 'required|exists:funds,id',
            'amount' => 'required|numeric|gt:0',
            'member_id' => 'nullable|exists:members,id',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'txn_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'required|string',
        ]);

        $fund = Fund::findOrFail($data['fund_id']);
        $this->authorize('update', $fund);

        try {
            $txn = $this->fundService->recordDisbursement($fund, (float) $data['amount'], $data);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AuditLog::record('fund.disbursed', $txn, [], $txn->toArray());

        return redirect()->route('funds.show', $fund)->with('success', __('Disbursement recorded successfully. Txn No: :no', ['no' => $txn->txn_no]));
    }

    public function create()
    {
        return view('funds.form', ['fund' => new Fund()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:funds,code',
            'type' => 'required|in:welfare,emergency,reserve,development,other',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $fund = $this->fundService->createFund($data);
        AuditLog::record('fund.created', $fund, [], $fund->toArray());

        return redirect()->route('funds.show', $fund)->with('success', __('Fund created successfully.'));
    }

    public function show(Fund $fund, Request $request)
    {
        $query = $fund->transactions()
            ->with(['member', 'savingsAccount', 'savingsTransaction', 'receiver', 'creator'])
            ->where('status', 'posted');

        if ($request->filled('date_from')) {
            $query->whereDate('txn_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('txn_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $transactions = $query->orderByDesc('txn_date')->orderByDesc('id')->paginate(20)->withQueryString();

        $totalCredit = $fund->transactions()->where('direction', 'credit')->where('status', 'posted')->sum('amount');
        $totalDebit = $fund->transactions()->where('direction', 'debit')->where('status', 'posted')->sum('amount');
        $contributingMembersCount = $fund->transactions()->whereNotNull('member_id')->distinct('member_id')->count('member_id');

        $members = Member::active()->orderBy('name')->get(['id', 'name', 'member_no']);

        return view('funds.show', compact('fund', 'transactions', 'totalCredit', 'totalDebit', 'contributingMembersCount', 'members'));
    }

    public function edit(Fund $fund)
    {
        return view('funds.form', compact('fund'));
    }

    public function update(Request $request, Fund $fund)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:20', Rule::unique('funds', 'code')->ignore($fund->id)],
            'type' => 'required|in:welfare,emergency,reserve,development,other',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $before = $fund->toArray();
        $fund->update($data);
        AuditLog::record('fund.updated', $fund, $before, $fund->toArray());

        return redirect()->route('funds.show', $fund)->with('success', __('Fund updated successfully.'));
    }

    public function destroy(Fund $fund)
    {
        if ($fund->transactions()->exists()) {
            return back()->with('error', __('Fund cannot be deleted because it has transactions.'));
        }

        AuditLog::record('fund.deleted', $fund, $fund->toArray(), []);
        $fund->delete();

        return redirect()->route('funds.index')->with('success', __('Fund deleted successfully.'));
    }

    public function disburse(Request $request, Fund $fund)
    {
        $this->authorize('update', $fund);

        $data = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'member_id' => 'nullable|exists:members,id',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'txn_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'required|string',
        ]);

        try {
            $txn = $this->fundService->recordDisbursement($fund, (float) $data['amount'], $data);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AuditLog::record('fund.disbursed', $txn, [], $txn->toArray());

        return back()->with('success', __('Disbursement recorded successfully. Txn No: :no', ['no' => $txn->txn_no]));
    }

    public function contribute(Request $request, Fund $fund)
    {
        $this->authorize('update', $fund);

        $data = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'member_id' => 'nullable|exists:members,id',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'txn_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $data['record_cash'] = true;

        try {
            $txn = $this->fundService->recordContribution($fund, (float) $data['amount'], $data);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AuditLog::record('fund.contributed', $txn, [], $txn->toArray());

        return back()->with('success', __('Direct contribution recorded successfully. Txn No: :no', ['no' => $txn->txn_no]));
    }
}

