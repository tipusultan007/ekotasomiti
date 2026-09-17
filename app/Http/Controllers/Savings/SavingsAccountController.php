<?php

namespace App\Http\Controllers\Savings;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsProgram;
use App\Models\SavingsTransaction;
use App\Services\AccountNumberService;
use App\Services\SavingsTransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SavingsAccountController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SavingsAccount::class, 'account');
    }

    public function index(Request $request)
    {
        $query = SavingsAccount::with(['member', 'program', 'area']);

        if (auth()->user()->isFieldOfficer()) {
            $query->whereIn('area_id', auth()->user()->officerAreaIds());
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        if ($request->filled('program_id')) {
            $query->where('savings_program_id', $request->input('program_id'));
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('account_no', 'like', "%{$request->input('search')}%")
                    ->orWhereHas('member', fn ($m) => $m->where('name', 'like', "%{$request->input('search')}%"));
            });
        }

        $accounts = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('savings.accounts.index', [
            'accounts' => $accounts,
            'programs' => SavingsProgram::orderBy('name')->get(),
            'areas' => auth()->user()->isFieldOfficer()
                ? Area::whereIn('id', auth()->user()->officerAreaIds())->orderBy('name')->get()
                : Area::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $areas = auth()->user()->isFieldOfficer()
            ? Area::active()->whereIn('id', auth()->user()->officerAreaIds())->orderBy('name')->get()
            : Area::active()->orderBy('name')->get();

        $members = auth()->user()->isFieldOfficer()
            ? Member::active()->whereIn('area_id', auth()->user()->officerAreaIds())->orderBy('name')->get()
            : Member::active()->orderBy('name')->get();

        return view('savings.accounts.form', [
            'account' => new SavingsAccount,
            'programs' => SavingsProgram::active()->orderBy('name')->get(),
            'areas' => $areas,
            'officers' => \App\Models\User::officers()->active()->orderBy('name')->get(),
            'members' => $members,
        ]);
    }

    public function memberDetails(Request $request, Member $member)
    {
        $this->authorize('create', SavingsAccount::class);

        if (auth()->user()->isFieldOfficer() && !in_array($member->area_id, auth()->user()->officerAreaIds())) {
            abort(403);
        }

        $member->load([
            'area',
            'fieldOfficer',
            'savingsAccounts' => fn ($q) => $q->where('status', 'active')->with('program'),
            'loans' => fn ($q) => $q->with(['product', 'schedules'])->orderByDesc('id'),
        ]);

        $activeLoans = $member->loans->filter(fn ($l) => in_array($l->status, ['active', 'disbursed', 'overdue']));
        $overdueLoans = $member->loans->filter(fn ($l) => $l->status === 'overdue');
        $closedLoans = $member->loans->filter(fn ($l) => $l->status === 'closed');
        $totalSavings = $member->savingsAccounts->sum('current_balance');
        $totalOutstanding = $activeLoans->sum('outstanding');

        return view('loans.applications._member_details', compact(
            'member',
            'activeLoans',
            'overdueLoans',
            'closedLoans',
            'totalSavings',
            'totalOutstanding'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $program = SavingsProgram::findOrFail($data['savings_program_id']);
        $area = $data['area_id'] ? Area::find($data['area_id']) : null;
        $data['account_no'] = app(AccountNumberService::class)->nextAccountNumber($program->prefix, $area?->code);
        $data['current_balance'] = 0;
        $data['created_by'] = auth()->id();

        if (empty($data['field_officer_id'])) {
            $data['field_officer_id'] = $area?->fieldOfficers()->first()?->id
                ?? Member::find($data['member_id'])?->field_officer_id;
        }

        $account = SavingsAccount::create($data);

        if ((float) $data['opening_balance'] > 0) {
            app(SavingsTransactionService::class)->accountOpening($account, [
                'amount' => $data['opening_balance'],
                'txn_date' => $data['opening_date'],
                'collection_date' => $data['opening_date'],
                'payment_method' => 'cash',
                'notes' => __('Opening balance'),
            ]);
        }

        AuditLog::record('savings_account.created', $account, [], $account->toArray());

        return redirect()->route('savings.accounts.index')->with('success', __('Savings account :no opened successfully.', ['no' => $account->account_no]));
    }

    public function edit(SavingsAccount $account)
    {
        $areas = auth()->user()->isFieldOfficer()
            ? Area::active()->whereIn('id', auth()->user()->officerAreaIds())->orderBy('name')->get()
            : Area::active()->orderBy('name')->get();

        $members = auth()->user()->isFieldOfficer()
            ? Member::active()->whereIn('area_id', auth()->user()->officerAreaIds())->orderBy('name')->get()
            : Member::active()->orderBy('name')->get();

        return view('savings.accounts.form', [
            'account' => $account,
            'programs' => SavingsProgram::active()->orderBy('name')->get(),
            'areas' => $areas,
            'officers' => \App\Models\User::officers()->active()->orderBy('name')->get(),
            'members' => $members,
        ]);
    }

    public function update(Request $request, SavingsAccount $account)
    {
        $data = $this->validateData($request);

        if (! $request->filled('status')) {
            $data['status'] = $account->status;
        }

        if (empty($data['field_officer_id'])) {
            $area = !empty($data['area_id']) ? Area::find($data['area_id']) : null;
            $data['field_officer_id'] = $area?->fieldOfficers()->first()?->id
                ?? Member::find($data['member_id'])?->field_officer_id
                ?? $account->field_officer_id;
        }

        $before = $account->toArray();
        $account->update($data);
        AuditLog::record('savings_account.updated', $account, $before, $account->toArray());

        return redirect()->route('savings.accounts.index')->with('success', __('Savings account updated successfully.'));
    }

    public function destroy(SavingsAccount $account)
    {
        if ($account->transactions()->exists()) {
            return back()->with('error', __('Account cannot be deleted because it has transactions. Close it instead.'));
        }

        AuditLog::record('savings_account.deleted', $account, $account->toArray(), []);
        $account->delete();

        return redirect()->route('savings.accounts.index')->with('success', __('Savings account deleted successfully.'));
    }

    public function transactions(SavingsAccount $account)
    {
        $this->authorize('view', $account);

        $account->load([
            'member.area',
            'member.fieldOfficer',
            'program',
            'transactions' => fn ($q) => $q->where('status', 'posted')->with(['receiver', 'fieldOfficer']),
        ]);

        return view('savings.accounts.transactions', compact('account'));
    }

    public function reverse(SavingsTransaction $transaction)
    {
        $this->authorize('reverse transactions');

        try {
            app(SavingsTransactionService::class)->delete($transaction);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLog::record('savings_transaction.deleted', $transaction, $transaction->toArray(), []);

        return back()->with('success', __('Savings transaction :no deleted. The account balance and cash entry were restored.', ['no' => $transaction->txn_no]));
    }

    public function closeForm(SavingsAccount $account)
    {
        $this->authorize('update', $account);

        return view('savings.accounts.close', compact('account'));
    }

    public function close(Request $request, SavingsAccount $account)
    {
        $this->authorize('update', $account);

        $data = $request->validate([
            'closing_reason' => 'required|string|max:255',
            'close_date' => 'required|date',
        ]);

        if ($account->status === 'closed') {
            return back()->with('error', __('Account is already closed.'));
        }

        $balance = (float) $account->current_balance;

        if ($balance > 0) {
            app(SavingsTransactionService::class)->accountClosing($account, [
                'amount' => $balance,
                'txn_date' => $data['close_date'],
                'collection_date' => $data['close_date'],
                'payment_method' => 'cash',
                'notes' => __('Account closing - payout'),
            ]);
        }

        $before = $account->toArray();
        $account->update([
            'status' => 'closed',
            'closed_date' => $data['close_date'],
            'closing_reason' => $data['closing_reason'],
        ]);

        AuditLog::record('savings_account.closed', $account, $before, $account->toArray());

        return redirect()->route('savings.accounts.index')->with('success', __('Savings account closed successfully.'));
    }

    protected function validateData(Request $request): array
    {
        $areaRule = 'nullable|exists:areas,id';
        $memberRule = 'required|exists:members,id';

        if (auth()->user()->isFieldOfficer()) {
            $officerAreaIds = implode(',', auth()->user()->officerAreaIds());
            $areaRule = "required|in:{$officerAreaIds}";
            $memberRule = [
                'required',
                Rule::exists('members', 'id')->whereIn('area_id', auth()->user()->officerAreaIds()),
            ];
        }

        $validated = $request->validate([
            'member_id' => $memberRule,
            'savings_program_id' => 'required|exists:savings_programs,id',
            'area_id' => $areaRule,
            'field_officer_id' => 'nullable|exists:users,id',
            'opening_date' => 'required|date',
            'opening_balance' => 'nullable|numeric|min:0',
            'min_deposit' => 'nullable|numeric|min:0',
            'expected_deposit' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,closed',
        ], [], [
            'status' => __('Status'),
        ]);

        $validated['status'] = $validated['status'] ?? 'active';

        return $validated;
    }
}