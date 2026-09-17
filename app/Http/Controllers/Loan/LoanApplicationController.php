<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Services\AccountNumberService;
use App\Services\LoanDisbursementService;
use Illuminate\Http\Request;

class LoanApplicationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(LoanApplication::class, 'application');
    }

    public function index(Request $request)
    {
        $query = LoanApplication::with(['member', 'product', 'fieldOfficer']);

        if (auth()->user()->isFieldOfficer()) {
            $query->whereIn('area_id', auth()->user()->officerAreaIds());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        $applications = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('loans.applications.index', compact('applications'));
    }

    public function create()
    {
        $areas = auth()->user()->isFieldOfficer()
            ? Area::active()->whereIn('id', auth()->user()->officerAreaIds())->orderBy('name')->get()
            : Area::active()->orderBy('name')->get();

        $members = auth()->user()->isFieldOfficer()
            ? Member::active()->whereIn('area_id', auth()->user()->officerAreaIds())->orderBy('name')->get()
            : Member::active()->orderBy('name')->get();

        return view('loans.applications.form', [
            'application' => new LoanApplication,
            'products' => LoanProduct::active()->orderBy('name')->get(),
            'areas' => $areas,
            'officers' => \App\Models\User::officers()->active()->orderBy('name')->get(),
            'members' => $members,
        ]);
    }

    public function memberDetails(Request $request, Member $member)
    {
        $this->authorize('create', LoanApplication::class);

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

        $product = LoanProduct::findOrFail($data['loan_product_id']);
        $installment = (new \App\Services\LoanScheduleService())->calculate(
            new \App\Models\Loan(['interest_type' => $product->interest_type, 'frequency' => $product->frequency]),
            (float) $data['requested_amount'],
            (int) $data['requested_term'],
            (float) $product->interest_rate
        )[2];

        $data['application_no'] = app(AccountNumberService::class)->nextTransactionNumber('loan_application', 'LA-', true, $data['application_date']);
        $data['created_by'] = auth()->id();
        $data['status'] = 'approved';
        $data['approved_amount'] = $data['requested_amount'];
        $data['approved_interest_rate'] = $product->interest_rate;
        $data['approved_term'] = $data['requested_term'];
        $data['approved_installment'] = $installment;
        $data['approved_by'] = auth()->id();
        $data['approved_at'] = now();

        if (empty($data['field_officer_id'])) {
            $area = !empty($data['area_id']) ? Area::find($data['area_id']) : null;
            $data['field_officer_id'] = $area?->fieldOfficers()->first()?->id
                ?? Member::find($data['member_id'])?->field_officer_id;
        }

        try {
            $loan = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request) {
                $application = LoanApplication::create($data);

                $this->syncGuarantors($application, $request->input('guarantors', []));

                AuditLog::record('loan_application.created', $application, [], $application->toArray());

                $loan = app(LoanDisbursementService::class)->disburse($application, $data);

                $this->syncDocuments($application, $loan, $request);

                AuditLog::record('loan.disbursed', $loan, [], $loan->toArray());

                return $loan;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('loans.show', $loan)
            ->with('success', __('Loan :no created and disbursed successfully.', ['no' => $loan->loan_no]));
    }

    public function show(LoanApplication $application)
    {
        $application->load(['member', 'product', 'area', 'fieldOfficer', 'guarantors', 'documents', 'creator', 'approver', 'loan']);

        return view('loans.applications.show', compact('application'));
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
                \Illuminate\Validation\Rule::exists('members', 'id')->whereIn('area_id', auth()->user()->officerAreaIds()),
            ];
        }

        return $request->validate([
            'member_id' => $memberRule,
            'loan_product_id' => 'required|exists:loan_products,id',
            'requested_amount' => 'required|numeric|gt:0',
            'requested_term' => 'required|integer|min:1',
            'purpose' => 'nullable|string|max:255',
            'area_id' => $areaRule,
            'field_officer_id' => 'nullable|exists:users,id',
            'application_date' => 'required|date',
            'disbursement_date' => 'required|date',
            'first_due_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'verification_info' => 'nullable|string',
            'guarantor_info' => 'nullable|string',
            'remarks' => 'nullable|string',
            'guarantors' => 'nullable|array',
            'guarantors.*.name' => 'required|string|max:255',
            'guarantors.*.relationship' => 'nullable|string|max:255',
            'guarantors.*.nid' => 'nullable|string|max:30',
            'guarantors.*.mobile' => 'nullable|string|max:20',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx|max:10240',
            'document_titles' => 'nullable|array',
            'document_titles.*' => 'nullable|string|max:255',
            'document_types' => 'nullable|array',
            'document_types.*' => 'nullable|string|max:50',
        ]);
    }

    protected function syncGuarantors(LoanApplication $application, array $guarantors): void
    {
        foreach ($guarantors as $guarantor) {
            if (! empty($guarantor['name'])) {
                $application->guarantors()->create($guarantor);
            }
        }
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
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        }
    }
}