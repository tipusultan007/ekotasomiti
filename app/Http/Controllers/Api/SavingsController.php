<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsProgram;
use App\Models\SavingsTransaction;
use App\Services\AccountNumberService;
use App\Services\SavingsTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingsController extends Controller
{
    public function programs(): JsonResponse
    {
        $programs = SavingsProgram::where('status', 'active')
            ->select('id', 'code', 'name', 'frequency', 'prefix', 'min_deposit', 'expected_deposit')
            ->orderBy('code')
            ->get();

        return response()->json($programs);
    }

    public function accounts(Request $request): JsonResponse
    {
        $query = SavingsAccount::with([
            'member:id,member_no,name,mobile',
            'program:id,name,code,frequency',
            'area:id,code,name',
        ])
            ->where('status', 'active')
            ->whereIn('area_id', $request->user()->officerAreaIds());

        if ($request->filled('frequency')) {
            $query->whereHas('program', fn ($q) => $q->where('frequency', $request->input('frequency')));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('account_no', 'like', "%{$search}%")
                    ->orWhereHas('member', fn ($mq) => $mq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        $accounts = $query->orderBy('account_no')
            ->paginate($request->input('per_page', 20));

        return response()->json($accounts);
    }

    public function memberDetails(Request $request, Member $member): JsonResponse
    {
        $areaIds = $request->user()->officerAreaIds();
        abort_unless(in_array($member->area_id, $areaIds, true), 403, 'Member area not assigned to officer.');

        $member->load([
            'area:id,code,name',
            'fieldOfficer:id,name',
            'savingsAccounts' => fn ($q) => $q->where('status', 'active')->with('program:id,name,code,frequency'),
            'loans' => fn ($q) => $q->with(['product:id,name,code,frequency', 'schedules'])->orderByDesc('id'),
        ]);

        $activeLoans = $member->loans->filter(fn ($l) => in_array($l->status, ['active', 'disbursed', 'overdue']));
        $overdueLoans = $member->loans->filter(fn ($l) => $l->status === 'overdue');
        $totalSavings = (float) $member->savingsAccounts->sum('current_balance');
        $totalOutstanding = (float) $activeLoans->sum('outstanding');

        return response()->json([
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'name_bn' => $member->name_bn,
                'member_no' => $member->member_no,
                'mobile' => $member->mobile,
                'nid' => $member->nid,
                'photo_url' => $member->photo_url,
                'status' => $member->status,
                'area' => $member->area ? ['id' => $member->area->id, 'code' => $member->area->code, 'name' => $member->area->name] : null,
            ],
            'savings_accounts' => $member->savingsAccounts->map(fn ($s) => [
                'id' => $s->id,
                'account_no' => $s->account_no,
                'program_name' => $s->program->name ?? '',
                'frequency' => $s->program->frequency ?? '',
                'current_balance' => (float) $s->current_balance,
                'opening_date' => $s->opening_date,
            ]),
            'active_loans_count' => $activeLoans->count(),
            'overdue_loans_count' => $overdueLoans->count(),
            'total_savings' => $totalSavings,
            'total_outstanding' => $totalOutstanding,
        ]);
    }

    public function storeAccount(Request $request): JsonResponse
    {
        $officer = $request->user();
        $officerAreaIds = $officer->officerAreaIds();

        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'savings_program_id' => 'required|exists:savings_programs,id',
            'opening_date' => 'required|date',
            'opening_balance' => 'nullable|numeric|min:0',
            'min_deposit' => 'nullable|numeric|min:0',
            'expected_deposit' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,closed',
            'notes' => 'nullable|string',
        ]);

        $member = Member::findOrFail($validated['member_id']);
        abort_unless(in_array((int) $member->area_id, $officerAreaIds, true), 403, 'Member area not assigned to officer.');

        $program = SavingsProgram::findOrFail($validated['savings_program_id']);
        $area = Area::find($member->area_id);
        $accountNo = app(AccountNumberService::class)->nextAccountNumber($program->prefix, $area?->code);

        $account = SavingsAccount::create([
            'member_id' => $member->id,
            'savings_program_id' => $program->id,
            'area_id' => $member->area_id,
            'field_officer_id' => $officer->id,
            'account_no' => $accountNo,
            'opening_date' => $validated['opening_date'],
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'min_deposit' => $validated['min_deposit'] ?? $program->min_deposit,
            'expected_deposit' => $validated['expected_deposit'] ?? $program->expected_deposit,
            'status' => $validated['status'] ?? 'active',
            'current_balance' => 0,
            'created_by' => $officer->id,
        ]);

        if ((float) $account->opening_balance > 0) {
            app(SavingsTransactionService::class)->accountOpening($account, [
                'amount' => $account->opening_balance,
                'txn_date' => $account->opening_date,
                'collection_date' => $account->opening_date,
                'payment_method' => 'cash',
                'notes' => __('Opening balance'),
            ]);
        }

        AuditLog::record('savings_account.created', $account, [], $account->toArray());

        return response()->json([
            'message' => __('Savings account :no opened successfully.', ['no' => $account->account_no]),
            'account' => $account->fresh(['member:id,member_no,name', 'program:id,name,code,frequency', 'area:id,code,name']),
        ], 201);
    }

    public function transactions(Request $request): JsonResponse
    {
        $query = SavingsTransaction::with([
            'account:id,account_no',
            'member:id,member_no,name',
            'program:id,name,code',
            'fieldOfficer:id,name',
        ])
            ->where('status', 'posted')
            ->whereIn('type', ['deposit', 'account_opening'])
            ->whereIn('area_id', $request->user()->officerAreaIds());

        if ($request->filled('date_from')) {
            $query->whereDate('txn_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('txn_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('frequency')) {
            $query->whereHas('program', fn ($q) => $q->where('frequency', $request->input('frequency')));
        }

        $transactions = $query->orderByDesc('txn_date')
            ->paginate($request->input('per_page', 20));

        return response()->json($transactions);
    }

    public function show(Request $request, SavingsAccount $account): JsonResponse
    {
        $areaIds = $request->user()->officerAreaIds();
        abort_unless(in_array($account->area_id, $areaIds), 403);

        $account->load([
            'member:id,member_no,name,name_bn,mobile,nid,address,status',
            'program:id,name,code,frequency,min_deposit,expected_deposit',
            'area:id,code,name',
            'fieldOfficer:id,name',
            'transactions' => fn ($q) => $q->where('status', 'posted')->orderByDesc('txn_date')->limit(30),
        ]);

        return response()->json($account);
    }
}
