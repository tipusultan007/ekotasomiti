<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoanApplicationRequest;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Services\LoanDisbursementService;
use App\Services\LoanScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    public function products(): JsonResponse
    {
        $products = LoanProduct::where('status', 'active')
            ->select('id', 'code', 'name', 'frequency', 'interest_rate', 'interest_type', 'min_amount', 'max_amount', 'min_term', 'max_term', 'processing_fee', 'insurance_fee', 'description')
            ->orderBy('name')
            ->get();

        return response()->json($products);
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
        $closedLoans = $member->loans->filter(fn ($l) => $l->status === 'closed');
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
            ]),
            'active_loans' => $activeLoans->map(fn ($l) => [
                'id' => $l->id,
                'loan_no' => $l->loan_no,
                'product_name' => $l->product->name ?? '',
                'principal_amount' => (float) $l->principal_amount,
                'installment_amount' => (float) $l->installment_amount,
                'outstanding' => (float) $l->outstanding,
                'status' => $l->status,
            ]),
            'overdue_count' => $overdueLoans->count(),
            'closed_count' => $closedLoans->count(),
            'total_savings' => $totalSavings,
            'total_outstanding' => $totalOutstanding,
            'is_eligible' => $overdueLoans->isEmpty() && $member->status === 'active',
            'ineligibility_reason' => $member->status !== 'active'
                ? __('Member is not active')
                : ($overdueLoans->isNotEmpty() ? __('Member has :count overdue loan(s)', ['count' => $overdueLoans->count()]) : null),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        Loan::refreshOverdueStatus();

        $query = Loan::with([
            'member:id,member_no,name,mobile',
            'product:id,name,code,frequency',
            'area:id,code,name',
            'fieldOfficer:id,name',
            'latestRepayment:id,loan_id,txn_no,amount,txn_date,payment_method',
        ])
            ->whereIn('status', ['disbursed', 'active', 'overdue'])
            ->whereIn('area_id', $request->user()->officerAreaIds());

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->input('frequency'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('loan_no', 'like', "%{$search}%")
                    ->orWhereHas('member', fn ($mq) => $mq->where('name', 'like', "%{$search}%"));
            });
        }

        $loans = $query->orderBy('loan_no')
            ->paginate($request->input('per_page', 20));

        return response()->json($loans);
    }

    public function show(Request $request, Loan $loan): JsonResponse
    {
        $areaIds = $request->user()->officerAreaIds();
        abort_unless(in_array($loan->area_id, $areaIds), 403);

        $loan->load([
            'member:id,member_no,name,mobile',
            'product:id,name,code,frequency,interest_rate,interest_type',
            'area:id,code,name',
            'fieldOfficer:id,name',
            'schedules',
            'transactions' => fn ($q) => $q->where('status', 'posted')->latest()->limit(20),
        ]);

        return response()->json($loan);
    }

    public function schedule(Request $request, Loan $loan): JsonResponse
    {
        $areaIds = $request->user()->officerAreaIds();
        abort_unless(in_array($loan->area_id, $areaIds), 403);

        $loan->load([
            'member:id,member_no,name',
            'product:id,name,code',
            'schedules',
        ]);

        return response()->json([
            'loan_no' => $loan->loan_no,
            'member_name' => $loan->member->name,
            'product_name' => $loan->product->name,
            'principal_amount' => $loan->principal_amount,
            'installment_amount' => $loan->installment_amount,
            'total_paid' => $loan->total_paid,
            'outstanding' => $loan->outstanding,
            'schedules' => $loan->schedules->map(fn ($s) => [
                'installment_no' => $s->installment_no,
                'due_date' => $s->due_date,
                'total' => $s->total,
                'paid' => $s->paid,
                'status' => $s->status,
            ]),
        ]);
    }

    public function storeApplication(LoanApplicationRequest $request): JsonResponse
    {
        $officer = $request->user();
        $data = $request->validated();

        $member = Member::findOrFail($data['member_id']);
        $product = LoanProduct::findOrFail($data['loan_product_id']);

        $calcInstallment = (new LoanScheduleService())->calculate(
            new Loan(['interest_type' => $product->interest_type, 'frequency' => $product->frequency]),
            (float) $data['requested_amount'],
            (int) $data['requested_term'],
            (float) $product->interest_rate
        )[2];

        $installment = !empty($data['installment_amount']) && (float) $data['installment_amount'] > 0
            ? (float) $data['installment_amount']
            : $calcInstallment;

        $data['installment_amount'] = $installment;
        $data['application_no'] = app(\App\Services\AccountNumberService::class)->nextTransactionNumber('loan_application', 'LA-', true, $data['application_date']);
        $data['created_by'] = $officer->id;
        $data['status'] = 'approved';
        $data['approved_amount'] = $data['requested_amount'];
        $data['approved_interest_rate'] = $product->interest_rate;
        $data['approved_term'] = $data['requested_term'];
        $data['approved_installment'] = $installment;
        $data['approved_by'] = $officer->id;
        $data['approved_at'] = now();
        $data['area_id'] = $member->area_id;
        $data['field_officer_id'] = $officer->id;

        $loan = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request) {
            $application = LoanApplication::create($data);

            if (! empty($request->input('guarantors'))) {
                foreach ($request->input('guarantors') as $guarantor) {
                    if (! empty($guarantor['name'])) {
                        $application->guarantors()->create($guarantor);
                    }
                }
            }

            $loan = app(LoanDisbursementService::class)->disburse($application, [
                'installment_amount' => $installment,
                'disbursement_date' => $data['disbursement_date'],
                'first_due_date' => $data['first_due_date'],
                'payment_method' => $data['payment_method'],
            ]);

            $this->syncDocuments($application, $loan, $request);

            return $loan;
        });

        return response()->json([
            'message' => 'Loan application submitted and disbursed successfully.',
            'application_no' => $application->application_no,
            'loan_no' => $loan->loan_no,
        ], 201);
    }

    protected function syncDocuments(LoanApplication $application, ?Loan $loan, Request $request): void
    {
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('loans/documents', 'public');
                    $title = $request->input("document_titles.{$index}")
                        ?? $request->input("documents.{$index}.title")
                        ?? $file->getClientOriginalName();
                    $type = $request->input("document_types.{$index}")
                        ?? $request->input("documents.{$index}.type")
                        ?? 'other';

                    $application->documents()->create([
                        'loan_id' => $loan?->id,
                        'type' => $type,
                        'title' => $title,
                        'file_path' => $path,
                        'created_by' => $request->user()->id,
                    ]);
                }
            }
        }
    }
}
