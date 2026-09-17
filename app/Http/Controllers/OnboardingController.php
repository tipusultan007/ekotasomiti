<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OnboardingController extends Controller
{
    public function create()
    {
        return view('onboarding.create', [
            'areas' => Area::with('fieldOfficers')->active()->orderBy('name')->get(),
            'programs' => SavingsProgram::active()->orderBy('name')->get(),
            'products' => LoanProduct::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
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
            'member.area_id' => 'nullable|exists:areas,id',
            'member.field_officer_id' => 'nullable|exists:users,id',
            'member.occupation' => 'nullable|string|max:255',
            'member.status' => 'required|in:active,inactive,suspended,closed',
            'member.notes' => 'nullable|string',
            'member.photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
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
            'savings.status' => 'required|in:active,closed',
            'savings.status' => 'nullable|in:active,closed',

            'loan.create_loan' => 'nullable|boolean',
            'loan.loan_product_id' => 'required_if:loan.create_loan,1|nullable|exists:loan_products,id',
            'loan.requested_amount' => 'required_if:loan.create_loan,1|nullable|numeric|gt:0',
            'loan.requested_term' => 'required_if:loan.create_loan,1|nullable|integer|min:1',
            'loan.purpose' => 'nullable|string|max:255',
            'loan.application_date' => 'required_if:loan.create_loan,1|nullable|date',
            'loan.disbursement_date' => 'required_if:loan.create_loan,1|nullable|date',
            'loan.first_due_date' => 'required_if:loan.create_loan,1|nullable|date',
            'loan.payment_method' => 'required_if:loan.create_loan,1|nullable|in:cash,bank,bkash,nagad,other',
            'loan.guarantors' => 'nullable|array',
            'loan.guarantors.*.name' => 'required|string|max:255',
            'loan.guarantors.*.relationship' => 'nullable|string|max:255',
            'loan.guarantors.*.nid' => 'nullable|string|max:30',
            'loan.guarantors.*.mobile' => 'nullable|string|max:20',
        ], [], [
            'nominees.*.name' => __('Nominee name'),
            'nominees.*.relationship' => __('Relation'),
            'nominees.*.nid' => __('NID'),
            'nominees.*.mobile' => __('Mobile'),
            'nominees.*.percentage' => __('Percentage'),
        ]);

        $this->authorize('create', Member::class);
        $this->authorize('create', SavingsAccount::class);

        $createLoan = $request->boolean('loan.create_loan');
        if ($createLoan) {
            $this->authorize('create', LoanApplication::class);
        }

        $resolveOfficerId = function (?int $areaId) {
            if ($areaId) {
                $area = Area::with('fieldOfficers')->find($areaId);
                $officer = $area?->fieldOfficers()->first();
                if ($officer) {
                    return $officer->id;
                }
            }
            if (auth()->user()->isFieldOfficer()) {
                return auth()->id();
            }
            return null;
        };

        try {
            $result = DB::transaction(function () use ($request, $data, $resolveOfficerId) {
                // 1. Member
                $memberData = $data['member'];
                unset($memberData['photo']);

                $memberData['member_no'] = app(AccountNumberService::class)->nextMemberNumber();
                $memberData['created_by'] = auth()->id();
                $memberData['field_officer_id'] = $resolveOfficerId($memberData['area_id'] ?? null);

                if ($request->hasFile('member.photo')) {
                    $memberData['photo_path'] = $request->file('member.photo')->store('members/photos', 'public');
                }

                $member = Member::create($memberData);

                foreach ($data['nominees'] ?? [] as $nominee) {
                    if (! empty($nominee['name'])) {
                        $member->nominees()->create($nominee);
                    }
                }

                AuditLog::record('member.created', $member, [], $member->toArray());

                // 2. Savings account (inherits area and officer from member)
                $savings = $data['savings'];
                $savingsAreaId = $member->area_id;
                $savingsOfficerId = $member->field_officer_id;
                $program = SavingsProgram::findOrFail($savings['savings_program_id']);
                $areaCode = $savingsAreaId ? (Area::find($savingsAreaId)?->code) : null;

                $account = SavingsAccount::create([
                    'member_id' => $member->id,
                    'savings_program_id' => $savings['savings_program_id'],
                    'area_id' => $savingsAreaId,
                    'field_officer_id' => $savingsOfficerId,
                    'opening_date' => $savings['opening_date'],
                    'opening_balance' => $savings['opening_balance'] ?? 0,
                    'min_deposit' => $savings['min_deposit'] ?? null,
                    'expected_deposit' => $savings['expected_deposit'] ?? null,
                    'status' => $savings['status'],
                    'status' => $savings['status'] ?? 'active',
                    'account_no' => app(AccountNumberService::class)->nextAccountNumber($program->prefix, $areaCode),
                    'current_balance' => 0,
                    'created_by' => auth()->id(),
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

                // 3. Optional loan (inherits area and officer from member)
                $loanNo = null;
                if ($request->boolean('loan.create_loan')) {
                    $loan = $data['loan'];
                    $loanAreaId = $member->area_id;
                    $loanOfficerId = $member->field_officer_id;
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
                        'area_id' => $loanAreaId,
                        'field_officer_id' => $loanOfficerId,
                        'application_date' => $loan['application_date'],
                        'status' => 'approved',
                        'approved_amount' => $loan['requested_amount'],
                        'approved_interest_rate' => $product->interest_rate,
                        'approved_term' => $loan['requested_term'],
                        'approved_installment' => $installment,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'application_no' => app(AccountNumberService::class)->nextTransactionNumber('loan_application', 'LA-', true, $loan['application_date']),
                        'created_by' => auth()->id(),
                    ]);

                    foreach ($data['loan']['guarantors'] ?? [] as $guarantor) {
                        if (! empty($guarantor['name'])) {
                            $application->guarantors()->create($guarantor);
                        }
                    }

                    AuditLog::record('loan_application.created', $application, [], $application->toArray());

                    $disbursedLoan = app(LoanDisbursementService::class)->disburse($application, [
                        'amount' => $loan['requested_amount'],
                        'interest_rate' => $product->interest_rate,
                        'term' => $loan['requested_term'],
                        'disbursement_date' => $loan['disbursement_date'],
                        'first_due_date' => $loan['first_due_date'],
                        'payment_method' => $loan['payment_method'],
                    ]);

                    AuditLog::record('loan.disbursed', $disbursedLoan, [], $disbursedLoan->toArray());
                    $loanNo = $disbursedLoan->loan_no;
                }

                return ['member' => $member, 'loanNo' => $loanNo];
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $message = __('Member :no and savings account created successfully.', ['no' => $result['member']->member_no]);
        if ($result['loanNo']) {
            $message .= ' ' . __('Loan :no disbursed successfully.', ['no' => $result['loanNo']]);
        }

        return redirect()->route('members.show', $result['member'])->with('success', $message);
    }
}