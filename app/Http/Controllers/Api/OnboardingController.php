<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsProgram;
use App\Services\AccountNumberService;
use App\Services\LoanDisbursementService;
use App\Services\LoanScheduleService;
use App\Services\SavingsTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $officer = $request->user();
        $officerAreaIds = $officer->officerAreaIds();

        if ($request->has('nominees') && is_array($request->input('nominees'))) {
            $cleanedNominees = array_values(array_filter($request->input('nominees'), function ($nominee) {
                if (!is_array($nominee)) {
                    return false;
                }
                return collect($nominee)->contains(function ($val) {
                    return !is_null($val) && trim((string)$val) !== '';
                });
            }));

            $request->merge([
                'nominees' => !empty($cleanedNominees) ? $cleanedNominees : null,
            ]);
        }

        $data = $request->validate([
            'member.membership_date' => 'required|date',
            'member.name' => 'required|string|max:255',
            'member.name_bn' => 'nullable|string|max:255',
            'member.father_husband_name' => 'nullable|string|max:255',
            'member.mother_name' => 'nullable|string|max:255',
            'member.dob' => 'nullable|date',
            'member.gender' => 'required|in:male,female,other',
            'member.mobile' => 'nullable|string|max:20',
            'member.nid' => 'nullable|string|max:30',
            'member.address' => 'nullable|string',
            'member.area_id' => 'required|exists:areas,id',
            'member.occupation' => 'nullable|string|max:255',
            'member.status' => 'nullable|in:active,inactive,suspended,closed',
            'member.notes' => 'nullable|string',
            'member.photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'nominees' => 'nullable|array',
            'nominees.*.name' => 'required|string|max:255',
            'nominees.*.relationship' => 'nullable|string|max:255',
            'nominees.*.nid' => 'nullable|string|max:30',
            'nominees.*.mobile' => 'nullable|string|max:20',
            'nominees.*.percentage' => 'nullable|numeric|min:0|max:100',

            'savings.savings_program_id' => 'required|exists:savings_programs,id',
            'savings.opening_date' => 'required|date',
            'savings.opening_balance' => 'nullable|numeric|min:0',
            'savings.min_deposit' => 'nullable|numeric|min:0',
            'savings.expected_deposit' => 'nullable|numeric|min:0',
            'savings.status' => 'nullable|in:active,closed',

            'loan.create_loan' => 'nullable|boolean',
            'loan.loan_product_id' => 'required_if:loan.create_loan,true,1|nullable|exists:loan_products,id',
            'loan.requested_amount' => 'required_if:loan.create_loan,true,1|nullable|numeric|gt:0',
            'loan.requested_term' => 'required_if:loan.create_loan,true,1|nullable|integer|min:1',
            'loan.purpose' => 'nullable|string|max:255',
            'loan.application_date' => 'required_if:loan.create_loan,true,1|nullable|date',
            'loan.disbursement_date' => 'required_if:loan.create_loan,true,1|nullable|date',
            'loan.first_due_date' => 'required_if:loan.create_loan,true,1|nullable|date',
            'loan.payment_method' => 'required_if:loan.create_loan,true,1|nullable|in:cash,bank,bkash,nagad,other',
            'loan.guarantors' => 'nullable|array',
            'loan.guarantors.*.name' => 'required|string|max:255',
            'loan.guarantors.*.relationship' => 'nullable|string|max:255',
            'loan.guarantors.*.nid' => 'nullable|string|max:30',
            'loan.guarantors.*.mobile' => 'nullable|string|max:20',
        ]);

        abort_unless(in_array((int) $data['member']['area_id'], $officerAreaIds, true), 403, 'Area not assigned to this field officer.');

        try {
            $result = DB::transaction(function () use ($request, $data, $officer) {
                // 1. Member Creation
                $memberData = $data['member'];
                unset($memberData['photo']);

                $memberData['member_no'] = app(AccountNumberService::class)->nextMemberNumber();
                $memberData['created_by'] = $officer->id;
                $memberData['field_officer_id'] = $officer->id;
                $memberData['status'] = $memberData['status'] ?? 'active';

                if ($request->hasFile('member.photo')) {
                    $memberData['photo_path'] = $request->file('member.photo')->store('members/photos', 'public');
                }

                $member = Member::create($memberData);

                if (! empty($data['nominees'])) {
                    foreach ($data['nominees'] as $nominee) {
                        if (! empty($nominee['name'])) {
                            $member->nominees()->create($nominee);
                        }
                    }
                }

                AuditLog::record('member.created', $member, [], $member->toArray());

                // 2. Savings Account Creation
                $savings = $data['savings'];
                $program = SavingsProgram::findOrFail($savings['savings_program_id']);
                $area = Area::find($member->area_id);
                $accountNo = app(AccountNumberService::class)->nextAccountNumber($program->prefix, $area?->code);

                $account = SavingsAccount::create([
                    'member_id' => $member->id,
                    'savings_program_id' => $savings['savings_program_id'],
                    'area_id' => $member->area_id,
                    'field_officer_id' => $officer->id,
                    'opening_date' => $savings['opening_date'],
                    'opening_balance' => $savings['opening_balance'] ?? 0,
                    'min_deposit' => $savings['min_deposit'] ?? $program->min_deposit,
                    'expected_deposit' => $savings['expected_deposit'] ?? $program->expected_deposit,
                    'status' => $savings['status'] ?? 'active',
                    'account_no' => $accountNo,
                    'current_balance' => 0,
                    'created_by' => $officer->id,
                ]);

                if ((float) $account->opening_balance > 0) {
                    app(SavingsTransactionService::class)->accountOpening($account, [
                        'amount' => $account->opening_balance,
                        'txn_date' => $account->opening_date,
                        'collection_date' => $account->opening_date,
                        'payment_method' => 'cash',
                        'notes' => __('Opening balance deposit'),
                    ]);
                }

                AuditLog::record('savings_account.created', $account, [], $account->toArray());

                // 3. Optional Loan Creation
                $loanRecord = null;
                if ($request->boolean('loan.create_loan')) {
                    $loan = $data['loan'];
                    $product = LoanProduct::findOrFail($loan['loan_product_id']);

                    $installment = (new LoanScheduleService())->calculate(
                        new \App\Models\Loan(['interest_type' => $product->interest_type, 'frequency' => $product->frequency]),
                        (float) $loan['requested_amount'],
                        (int) $loan['requested_term'],
                        (float) $product->interest_rate
                    )[2];

                    $application = LoanApplication::create([
                        'member_id' => $member->id,
                        'loan_product_id' => $loan['loan_product_id'],
                        'requested_amount' => $loan['requested_amount'],
                        'requested_term' => $loan['requested_term'],
                        'purpose' => $loan['purpose'] ?? null,
                        'area_id' => $member->area_id,
                        'field_officer_id' => $officer->id,
                        'application_date' => $loan['application_date'],
                        'status' => 'approved',
                        'approved_amount' => $loan['requested_amount'],
                        'approved_interest_rate' => $product->interest_rate,
                        'approved_term' => $loan['requested_term'],
                        'approved_installment' => $installment,
                        'approved_by' => $officer->id,
                        'approved_at' => now(),
                        'application_no' => app(AccountNumberService::class)->nextTransactionNumber('loan_application', 'LA-', true, $loan['application_date']),
                        'created_by' => $officer->id,
                    ]);

                    if (! empty($loan['guarantors'])) {
                        foreach ($loan['guarantors'] as $guarantor) {
                            if (! empty($guarantor['name'])) {
                                $application->guarantors()->create($guarantor);
                            }
                        }
                    }

                    AuditLog::record('loan_application.created', $application, [], $application->toArray());

                    $loanRecord = app(LoanDisbursementService::class)->disburse($application, [
                        'amount' => $loan['requested_amount'],
                        'interest_rate' => $product->interest_rate,
                        'term' => $loan['requested_term'],
                        'disbursement_date' => $loan['disbursement_date'],
                        'first_due_date' => $loan['first_due_date'],
                        'payment_method' => $loan['payment_method'],
                    ]);

                    AuditLog::record('loan.disbursed', $loanRecord, [], $loanRecord->toArray());
                }

                return [
                    'member' => $member->load(['area:id,code,name', 'nominees']),
                    'savings_account' => $account->load('program:id,name,code,frequency'),
                    'loan' => $loanRecord ? [
                        'id' => $loanRecord->id,
                        'loan_no' => $loanRecord->loan_no,
                        'principal_amount' => $loanRecord->principal_amount,
                        'installment_amount' => $loanRecord->installment_amount,
                    ] : null,
                ];
            });

            return response()->json([
                'message' => __('Member onboarded successfully.'),
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Onboarding failed: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }
}
