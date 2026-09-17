<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\LoanTransaction;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsProgram;
use App\Models\SavingsTransaction;
use App\Models\User;
use App\Services\PdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view reports');
    }

    public function index()
    {
        return view('reports.index');
    }

    public function members(Request $request)
    {
        $areaScope = auth()->user()->isFieldOfficer() ? auth()->user()->officerAreaIds() : null;

        $query = Member::with(['area', 'fieldOfficer'])
            ->withCount('savingsAccounts')
            ->withSum('savingsAccounts as total_savings', 'current_balance');

        if ($areaScope) {
            $query->whereIn('area_id', $areaScope);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('membership_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('membership_date', '<=', $request->input('date_to'));
        }

        $members = $query->orderBy('member_no')->get();
        $areas = $areaScope ? Area::whereIn('id', $areaScope)->orderBy('name')->get() : Area::orderBy('name')->get();

        if ($request->input('export') === 'csv') {
            return $this->csv([__('Member No'), __('Name'), __('Mobile'), __('NID'), __('Area'), __('Officer'), __('Membership Date'), __('Status'), __('Savings Balance')],
                $members->map(fn ($m) => [$m->member_no, $m->name, $m->mobile, $m->nid, $m->area?->name, $m->fieldOfficer?->name, $m->membership_date?->format('d-m-Y'), $m->status, $m->total_savings]),
                'members-report');
        }

        return view('reports.members', compact('members', 'areas'));
    }

    public function savings(Request $request)
    {
        $areaScope = auth()->user()->isFieldOfficer() ? auth()->user()->officerAreaIds() : null;

        $query = SavingsTransaction::with(['member', 'account', 'program', 'area', 'fieldOfficer'])
            ->where('status', 'posted');

        if ($areaScope) {
            $query->whereIn('area_id', $areaScope);
        }

        if (auth()->user()->isFieldOfficer()) {
            $query->where(fn ($q) => $q->where('field_officer_id', auth()->id())->orWhere('received_by', auth()->id()));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('officer_id')) {
            $query->where('field_officer_id', $request->input('officer_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('txn_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('txn_date', '<=', $request->input('date_to'));
        }

        $transactions = $query->orderByDesc('txn_date')->get();

        $summary = [
            'deposits' => $transactions->where('type', 'deposit')->sum('amount'),
            'withdrawals' => $transactions->where('type', 'withdrawal')->sum('amount'),
            'count' => $transactions->count(),
        ];

        $balancesQuery = SavingsAccount::active()->with('member');
        if ($areaScope) {
            $balancesQuery->whereIn('area_id', $areaScope);
        }
        $balances = $balancesQuery->orderBy('account_no')->get();

        $areas = $areaScope ? Area::whereIn('id', $areaScope)->orderBy('name')->get() : Area::orderBy('name')->get();
        $officers = auth()->user()->isFieldOfficer() ? User::where('id', auth()->id())->get() : User::officers()->active()->orderBy('name')->get();

        if ($request->input('export') === 'csv') {
            return $this->csv([__('Txn No'), __('Date'), __('Member'), __('Account'), __('Program'), __('Type'), __('Amount'), __('Area'), __('Officer'), __('Method')],
                $transactions->map(fn ($t) => [$t->txn_no, $t->txn_date?->format('d-m-Y'), $t->member?->name, $t->account?->account_no, $t->program?->name, $t->type, $t->amount, $t->area?->name, $t->fieldOfficer?->name, $t->payment_method]),
                'savings-transactions-report');
        }

        return view('reports.savings', compact('transactions', 'summary', 'balances', 'areas', 'officers'));
    }

    public function loans(Request $request)
    {
        $areaScope = auth()->user()->isFieldOfficer() ? auth()->user()->officerAreaIds() : null;

        $query = Loan::with(['member', 'product', 'area', 'fieldOfficer', 'schedules']);

        if ($areaScope) {
            $query->whereIn('area_id', $areaScope);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('product_id')) {
            $query->where('loan_product_id', $request->input('product_id'));
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('disbursement_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('disbursement_date', '<=', $request->input('date_to'));
        }

        $loans = $query->orderBy('loan_no')->get();

        foreach ($loans as $loan) {
            $loan->repaid_principal = (float) $loan->transactions()
                ->where('status', 'posted')
                ->where('type', 'repayment')
                ->sum('principal_paid');
            $loan->outstanding_principal = max(0, (float) $loan->principal_amount - $loan->repaid_principal);
            $loan->overdue_amount = $loan->schedules
                ->whereIn('status', ['due', 'partial'])
                ->where('due_date', '<', now()->toDateString())
                ->sum(fn ($s) => max(0, (float) $s->total - (float) $s->paid));
        }

        $summary = [
            'disbursed' => $loans->sum('principal_amount'),
            'repaid' => $loans->sum('repaid_principal'),
            'outstanding' => $loans->sum('outstanding_principal'),
            'overdue' => $loans->sum('overdue_amount'),
        ];

        $products = LoanProduct::orderBy('name')->get();
        $areas = $areaScope ? Area::whereIn('id', $areaScope)->orderBy('name')->get() : Area::orderBy('name')->get();

        if ($request->input('export') === 'csv') {
            return $this->csv([__('Loan No'), __('Member'), __('Product'), __('Principal'), __('Interest Rate'), __('Term'), __('Installment'), __('Total Payable'), __('Repaid Principal'), __('Outstanding'), __('Overdue'), __('Status'), __('Disbursement')],
                $loans->map(fn ($l) => [$l->loan_no, $l->member?->name, $l->product?->name, $l->principal_amount, $l->interest_rate, $l->term, $l->installment_amount, $l->total_payable, $l->repaid_principal, $l->outstanding_principal, $l->overdue_amount, $l->status, $l->disbursement_date?->format('d-m-Y')]),
                'loans-report');
        }

        return view('reports.loans', compact('loans', 'summary', 'products', 'areas'));
    }

    public function collections(Request $request)
    {
        $areaScope = auth()->user()->isFieldOfficer() ? auth()->user()->officerAreaIds() : null;

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $type = $request->input('type');

        $collections = collect();
        $savingsTotal = 0.0;
        $principalTotal = 0.0;
        $interestTotal = 0.0;
        $lateTotal = 0.0;

        if (! $type || $type === 'savings') {
            $savingsTxn = SavingsTransaction::with(['member', 'area', 'fieldOfficer'])
                ->where('status', 'posted')
                ->whereBetween('txn_date', [$dateFrom, $dateTo])
                ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->input('area_id')))
                ->when($areaScope, fn ($q) => $q->whereIn('area_id', $areaScope))
                ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->where(fn ($sq) => $sq->where('field_officer_id', auth()->id())->orWhere('received_by', auth()->id())))
                ->when($request->filled('officer_id'), fn ($q) => $q->where('field_officer_id', $request->input('officer_id')))
                ->orderByDesc('txn_date')
                ->get();

            foreach ($savingsTxn as $t) {
                $collections->push((object) [
                    'date' => $t->txn_date,
                    'type' => 'savings',
                    'member' => $t->member,
                    'reference' => $t->txn_no,
                    'amount' => (float) $t->amount,
                    'area' => $t->area,
                    'officer' => $t->fieldOfficer,
                ]);
                $savingsTotal += (float) $t->amount;
            }
        }

        if (! $type || $type === 'loan') {
            $loanTxn = LoanTransaction::with(['member', 'area', 'fieldOfficer'])
                ->where('status', 'posted')
                ->where('type', 'repayment')
                ->whereBetween('txn_date', [$dateFrom, $dateTo])
                ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->input('area_id')))
                ->when($areaScope, fn ($q) => $q->whereIn('area_id', $areaScope))
                ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->where(fn ($sq) => $sq->where('field_officer_id', auth()->id())->orWhere('received_by', auth()->id())))
                ->when($request->filled('officer_id'), fn ($q) => $q->where('field_officer_id', $request->input('officer_id')))
                ->orderByDesc('txn_date')
                ->get();

            foreach ($loanTxn as $t) {
                $collections->push((object) [
                    'date' => $t->txn_date,
                    'type' => 'loan',
                    'member' => $t->member,
                    'reference' => $t->txn_no,
                    'amount' => (float) $t->amount,
                    'area' => $t->area,
                    'officer' => $t->fieldOfficer,
                ]);
                $principalTotal += (float) $t->principal_paid;
                $interestTotal += (float) $t->interest_paid;
                $lateTotal += (float) $t->late_fee;
            }
        }

        $collections = $collections->sortByDesc('date')->values();

        $summary = [
            'savings' => $savingsTotal,
            'loan_principal' => $principalTotal,
            'loan_interest' => $interestTotal,
            'late_fees' => $lateTotal,
            'total' => $savingsTotal + $principalTotal + $interestTotal + $lateTotal,
        ];

        $areas = $areaScope ? Area::whereIn('id', $areaScope)->orderBy('name')->get() : Area::orderBy('name')->get();
        $officers = auth()->user()->isFieldOfficer() ? User::where('id', auth()->id())->get() : User::officers()->active()->orderBy('name')->get();

        if ($request->input('export') === 'csv') {
            return $this->csv([__('Date'), __('Type'), __('Member'), __('Reference'), __('Amount'), __('Area'), __('Officer')],
                $collections->map(fn ($c) => [$c->date?->format('d-m-Y'), $c->type, $c->member?->name, $c->reference, $c->amount, $c->area?->name, $c->officer?->name]),
                'collections-report');
        }

        return view('reports.collections', compact('collections', 'summary', 'areas', 'officers', 'dateFrom', 'dateTo'));
    }

    public function cash(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $transactions = CashTransaction::posted()
            ->with(['creator', 'fieldOfficer'])
            ->whereHas('register', fn ($q) => $q->whereBetween('register_date', [$dateFrom, $dateTo]))
            ->orderBy('created_at')
            ->get();

        $summary = [
            'cash_in' => $transactions->where('direction', 'in')->sum('amount'),
            'cash_out' => $transactions->where('direction', 'out')->sum('amount'),
            'expenses' => Expense::where('status', 'posted')->whereBetween('expense_date', [$dateFrom, $dateTo])->sum('amount'),
            'income' => Income::where('status', 'posted')->whereBetween('income_date', [$dateFrom, $dateTo])->sum('amount'),
        ];

        if ($request->input('export') === 'csv') {
            return $this->csv([__('Txn No'), __('Date'), __('Type'), __('Direction'), __('Amount'), __('Method'), __('Reference'), __('Notes'), __('Recorded By')],
                $transactions->map(fn ($t) => [$t->txn_no, $t->created_at?->format('d-m-Y'), $t->type, $t->direction, $t->amount, $t->payment_method, $t->reference, $t->notes, $t->creator?->name]),
                'cash-transactions-report');
        }

        return view('reports.cash', compact('transactions', 'summary', 'dateFrom', 'dateTo'));
    }

    public function areas(Request $request)
    {
        $areas = Area::withCount('members')
            ->withSum('savingsAccounts as savings_balance', 'current_balance')
            ->withSum('loans as loans_disbursed', 'principal_amount')
            ->withSum('loans as outstanding', 'outstanding')
            ->orderBy('name')
            ->get();

        if ($request->input('export') === 'csv') {
            return $this->csv([__('Area'), __('Code'), __('Members'), __('Savings Balance'), __('Loans Disbursed'), __('Outstanding')],
                $areas->map(fn ($a) => [$a->name, $a->code, $a->members_count, $a->savings_balance, $a->loans_disbursed, $a->outstanding]),
                'areas-report');
        }

        return view('reports.areas', compact('areas'));
    }

    public function officers(Request $request)
    {
        $officers = User::officers()
            ->with('areas')
            ->withCount('members')
            ->withSum('loans as outstanding', 'outstanding')
            ->orderBy('name')
            ->get();

        foreach ($officers as $officer) {
            $officer->savings_collected = SavingsTransaction::where('field_officer_id', $officer->id)
                ->where('status', 'posted')->sum('amount');
            $officer->loan_collected = LoanTransaction::where('field_officer_id', $officer->id)
                ->where('status', 'posted')->where('type', 'repayment')->sum('amount');
            $officer->due_today = LoanSchedule::whereHas('loan', fn ($q) => $q->where('field_officer_id', $officer->id))
                ->whereIn('status', ['due', 'partial'])
                ->whereDate('due_date', '<=', now()->toDateString())
                ->get()
                ->sum(fn ($s) => max(0, (float) $s->total - (float) $s->paid));
        }

        if ($request->input('export') === 'csv') {
            return $this->csv([__('Officer'), __('Email'), __('Phone'), __('Members'), __('Savings Collected'), __('Loan Collected'), __('Outstanding'), __('Due Today')],
                $officers->map(fn ($o) => [$o->name, $o->email, $o->phone, $o->members_count, $o->savings_collected, $o->loan_collected, $o->outstanding, $o->due_today]),
                'officers-report');
        }

        return view('reports.officers', compact('officers'));
    }

    public function summary(Request $request)
    {
        $data = $this->buildSummary($request);

        return view('reports.summary', $data);
    }

    public function summaryPdf(Request $request)
    {
        $data = $this->buildSummary($request);

        return PdfService::streamView('reports.summary_pdf', $data, 'financial-summary-' . $request->input('date_from', 'report') . '-' . $request->input('date_to', '') . '.pdf');
    }

    public function sheet(Request $request)
    {
        $data = $this->buildSheet($request);

        return view('reports.sheet', $data);
    }

    public function sheetPdf(Request $request)
    {
        $data = $this->buildSheet($request);

        return PdfService::streamView('reports.sheet_pdf', $data, 'monthly-sheet-' . ($data['month'] ?? 'report') . '.pdf', 'A4', 'L');
    }

    protected function buildSheet(Request $request): array
    {
        $areaScope = auth()->user()->isFieldOfficer() ? auth()->user()->officerAreaIds() : null;

        $month = $request->input('month', now()->format('Y-m'));
        $areaId = $request->filled('area_id') ? $request->integer('area_id') : null;
        $officerId = $request->filled('officer_id') ? $request->integer('officer_id') : null;
        $type = $request->input('type', 'savings'); // savings | loan
        $frequency = $request->input('frequency', 'daily'); // daily | weekly | monthly

        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $daysInMonth = $end->day;

        if ($type === 'loan') {
            $txnModel = LoanTransaction::class;
            $idCol = 'loan_id';
            $types = ['repayment'];
            $accounts = Loan::with('member')
                ->whereIn('status', ['disbursed', 'active', 'overdue'])
                ->where('frequency', $frequency)
                ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
                ->when($areaScope, fn ($q) => $q->whereIn('area_id', $areaScope))
                ->when($officerId, fn ($q) => $q->where('field_officer_id', $officerId))
                ->orderBy('loan_no')
                ->get();
            $accountNo = fn ($a) => $a->loan_no;
        } else {
            $txnModel = SavingsTransaction::class;
            $idCol = 'savings_account_id';
            $types = ['deposit', 'account_opening'];
            $accounts = SavingsAccount::with('member')
                ->where('status', 'active')
                ->whereHas('program', fn ($q) => $q->where('frequency', $frequency))
                ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
                ->when($areaScope, fn ($q) => $q->whereIn('area_id', $areaScope))
                ->when($officerId, fn ($q) => $q->where('field_officer_id', $officerId))
                ->orderBy('account_no')
                ->get();
            $accountNo = fn ($a) => $a->account_no;
        }

        $raw = $txnModel::query()
            ->where('status', 'posted')
            ->whereIn('type', $types)
            ->whereBetween('txn_date', [$start, $end])
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->when($areaScope, fn ($q) => $q->whereIn('area_id', $areaScope))
            ->when($officerId, fn ($q) => $q->where('field_officer_id', $officerId))
            ->select($idCol, DB::raw('DAY(txn_date) as day'), DB::raw('COALESCE(SUM(amount),0) as amt'))
            ->groupBy($idCol, DB::raw('DAY(txn_date)'))
            ->get();

        $matrix = [];
        foreach ($raw as $r) {
            $matrix[$r->{$idCol}][$r->day] = (float) $r->amt;
        }

        $rows = $accounts->map(function ($acc) use ($matrix, $daysInMonth, $accountNo) {
            $byDay = $matrix[$acc->id] ?? [];
            $cells = [];
            $rowTotal = 0;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $v = $byDay[$d] ?? 0;
                $cells[$d] = $v;
                $rowTotal += $v;
            }

            return (object) [
                'member_no' => $acc->member?->member_no,
                'member_name' => $acc->member?->name,
                'member_phone' => $acc->member?->mobile,
                'account_no' => $accountNo($acc),
                'cells' => $cells,
                'row_total' => $rowTotal,
            ];
        });

        $columnTotals = [];
        $grandTotal = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $sum = 0;
            foreach ($rows as $row) {
                $sum += $row->cells[$d];
            }
            $columnTotals[$d] = $sum;
            $grandTotal += $sum;
        }

        $areas = $areaScope ? Area::whereIn('id', $areaScope)->orderBy('name')->get() : Area::orderBy('name')->get();
        $officers = auth()->user()->isFieldOfficer() ? User::where('id', auth()->id())->get() : User::officers()->active()->orderBy('name')->get();

        $orgName = \App\Models\Setting::get('org_name', config('app.name'));
        $orgAddress = \App\Models\Setting::get('org_address', '');
        $orgPhone = \App\Models\Setting::get('org_phone', '');
        $monthLabel = $start->format('F-Y');

        return compact(
            'rows', 'columnTotals', 'grandTotal', 'daysInMonth', 'month', 'monthLabel', 'type', 'frequency', 'areas', 'officers',
            'areaId', 'officerId', 'start', 'end', 'orgName', 'orgAddress', 'orgPhone'
        );
    }

    protected function buildSummary(Request $request): array
    {
        $areaScope = auth()->user()->isFieldOfficer() ? auth()->user()->officerAreaIds() : null;

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $areaId = $request->filled('area_id') ? $request->integer('area_id') : null;
        $officerId = $request->filled('officer_id') ? $request->integer('officer_id') : null;
        $programId = $request->filled('savings_program_id') ? $request->integer('savings_program_id') : null;
        $productId = $request->filled('loan_product_id') ? $request->integer('loan_product_id') : null;

        $savingsQuery = function () use ($areaId, $officerId, $programId, $areaScope) {
            return SavingsTransaction::query()->where('status', 'posted')
                ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
                ->when($areaScope, fn ($q) => $q->whereIn('area_id', $areaScope))
                ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->where(fn ($sq) => $sq->where('field_officer_id', auth()->id())->orWhere('received_by', auth()->id())))
                ->when($officerId, fn ($q) => $q->where('field_officer_id', $officerId))
                ->when($programId, fn ($q) => $q->where('savings_program_id', $programId));
        };

        $loanQuery = function () use ($areaId, $officerId, $productId, $areaScope) {
            return Loan::query()
                ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
                ->when($areaScope, fn ($q) => $q->whereIn('area_id', $areaScope))
                ->when($officerId, fn ($q) => $q->where('field_officer_id', $officerId))
                ->when($productId, fn ($q) => $q->where('loan_product_id', $productId));
        };

        $collection = (clone $savingsQuery())
            ->whereIn('type', ['deposit', 'account_opening'])
            ->whereBetween('txn_date', [$dateFrom, $dateTo])
            ->sum('amount');

        $withdrawn = (clone $savingsQuery())
            ->where('type', 'withdrawal')
            ->whereBetween('txn_date', [$dateFrom, $dateTo])
            ->sum('amount');

        $balanceAsOfEnd = (clone $savingsQuery())
            ->whereDate('txn_date', '<=', $dateTo)
            ->whereIn('type', ['deposit', 'account_opening', 'withdrawal'])
            ->selectRaw("COALESCE(SUM(CASE WHEN type IN ('deposit','account_opening') THEN amount ELSE -amount END),0) as bal")
            ->value('bal');

        $loanProvided = (clone $loanQuery())
            ->whereBetween('disbursement_date', [$dateFrom, $dateTo])
            ->sum('principal_amount');

        $loanPaid = LoanTransaction::query()
            ->where('status', 'posted')->where('type', 'repayment')
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->when($areaScope, fn ($q) => $q->whereIn('area_id', $areaScope))
            ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->where(fn ($sq) => $sq->where('field_officer_id', auth()->id())->orWhere('received_by', auth()->id())))
            ->when($officerId, fn ($q) => $q->where('field_officer_id', $officerId))
            ->when($productId, fn ($q) => $q->whereHas('loan', fn ($l) => $l->where('loan_product_id', $productId)))
            ->whereBetween('txn_date', [$dateFrom, $dateTo])
            ->sum('amount');

        $loanRemain = (clone $loanQuery())
            ->whereIn('status', ['disbursed', 'active', 'overdue'])
            ->whereDate('disbursement_date', '<=', $dateTo)
            ->selectRaw("COALESCE(SUM(principal_amount - (SELECT COALESCE(SUM(principal_paid),0) FROM loan_transactions lt WHERE lt.loan_id = loans.id AND lt.status = 'posted' AND lt.type = 'repayment')),0) as rem")
            ->value('rem');

        $summary = [
            'collection' => (float) $collection,
            'withdrawn' => (float) $withdrawn,
            'net_savings' => (float) $collection - (float) $withdrawn,
            'balance_as_of_end' => (float) $balanceAsOfEnd,
            'loan_provided' => (float) $loanProvided,
            'loan_paid' => (float) $loanPaid,
            'loan_remain' => (float) $loanRemain,
        ];

        $savingsByProgram = (clone $savingsQuery())
            ->whereBetween('txn_date', [$dateFrom, $dateTo])
            ->whereIn('type', ['deposit', 'account_opening', 'withdrawal'])
            ->select('savings_program_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN type IN ('deposit','account_opening') THEN amount ELSE 0 END),0) as collected")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END),0) as withdrawn")
            ->groupBy('savings_program_id')
            ->with('program:id,name')
            ->orderByDesc(DB::raw('collected'))
            ->get();

        $loanByProduct = (clone $loanQuery())
            ->whereBetween('disbursement_date', [$dateFrom, $dateTo])
            ->select('loan_product_id', DB::raw('COALESCE(SUM(principal_amount),0) as provided'))
            ->groupBy('loan_product_id')
            ->with('product:id,name')
            ->orderByDesc(DB::raw('provided'))
            ->get();

        $repaidByProduct = LoanTransaction::query()
            ->where('loan_transactions.status', 'posted')->where('loan_transactions.type', 'repayment')
            ->when($areaId, fn ($q) => $q->where('loan_transactions.area_id', $areaId))
            ->when($areaScope, fn ($q) => $q->whereIn('loan_transactions.area_id', $areaScope))
            ->when(auth()->user()->isFieldOfficer(), fn ($q) => $q->where(fn ($sq) => $sq->where('loan_transactions.field_officer_id', auth()->id())->orWhere('loan_transactions.received_by', auth()->id())))
            ->when($officerId, fn ($q) => $q->where('loan_transactions.field_officer_id', $officerId))
            ->whereBetween('loan_transactions.txn_date', [$dateFrom, $dateTo])
            ->join('loans', 'loans.id', '=', 'loan_transactions.loan_id')
            ->when($productId, fn ($q) => $q->where('loans.loan_product_id', $productId))
            ->select('loans.loan_product_id', DB::raw('COALESCE(SUM(loan_transactions.amount),0) as repaid'))
            ->groupBy('loans.loan_product_id')
            ->pluck('repaid', 'loan_product_id');

        foreach ($loanByProduct as $row) {
            $row->repaid = (float) ($repaidByProduct[$row->loan_product_id] ?? 0);
        }

        $areas = $areaScope ? Area::whereIn('id', $areaScope)->orderBy('name')->get() : Area::orderBy('name')->get();
        $officers = auth()->user()->isFieldOfficer() ? User::where('id', auth()->id())->get() : User::officers()->active()->orderBy('name')->get();
        $programs = SavingsProgram::orderBy('name')->get();
        $products = LoanProduct::orderBy('name')->get();

        return compact(
            'summary', 'savingsByProgram', 'loanByProduct', 'areas', 'officers', 'programs', 'products',
            'dateFrom', 'dateTo', 'areaId', 'officerId', 'programId', 'productId'
        );
    }

    public function export(Request $request)
    {
        $report = $request->input('report');

        return match ($report) {
            'members' => $this->members($request->merge(['export' => 'csv'])),
            'savings' => $this->savings($request->merge(['export' => 'csv'])),
            'loans' => $this->loans($request->merge(['export' => 'csv'])),
            'collections' => $this->collections($request->merge(['export' => 'csv'])),
            'cash' => $this->cash($request->merge(['export' => 'csv'])),
            'areas' => $this->areas($request->merge(['export' => 'csv'])),
            'officers' => $this->officers($request->merge(['export' => 'csv'])),
            default => back()->with('error', __('Unknown report type.')),
        };
    }

    protected function csv(array $headers, \Illuminate\Support\Collection $rows, string $filename)
    {
        $filename = Str::slug($filename) . '-' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, (array) $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}