<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberDocument;
use App\Models\MemberNominee;
use App\Services\AccountNumberService;
use App\Services\PdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Member::class, 'member');
    }

    public function index(Request $request)
    {
        $query = Member::with(['area', 'fieldOfficer']);

        if (auth()->user()->isFieldOfficer()) {
            $query->whereIn('area_id', auth()->user()->officerAreaIds());
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('member_no', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('nid', 'like', "%{$search}%");
            });
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $members = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $areas = auth()->user()->isFieldOfficer()
            ? Area::whereIn('id', auth()->user()->officerAreaIds())->orderBy('name')->get()
            : Area::orderBy('name')->get();

        return view('members.index', [
            'members' => $members,
            'areas' => $areas,
        ]);
    }

    public function quickSearch(Request $request)
    {
        $this->authorize('viewAny', Member::class);

        $term = trim($request->input('q', ''));
        if (strlen($term) < 1) {
            return response()->json([]);
        }

        $query = Member::with([
            'area:id,name',
            'savingsAccounts' => fn ($q) => $q->select('id', 'member_id', 'account_no', 'status')->where('status', 'active'),
            'loans' => fn ($q) => $q->select('id', 'member_id', 'loan_no', 'status')->whereIn('status', ['active', 'disbursed', 'overdue']),
        ]);

        if (auth()->user()->isFieldOfficer()) {
            $query->whereIn('area_id', auth()->user()->officerAreaIds());
        }

        $query->where(function ($q) use ($term) {
            $q->where('member_no', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('name_bn', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%")
                ->orWhere('nid', 'like', "%{$term}%")
                ->orWhereHas('savingsAccounts', fn ($sq) => $sq->where('account_no', 'like', "%{$term}%"))
                ->orWhereHas('loans', fn ($lq) => $lq->where('loan_no', 'like', "%{$term}%"));

            if (is_numeric($term)) {
                $q->orWhere('id', (int) $term);
            }
        });

        $members = $query->limit(10)->get();

        return response()->json($members->map(function ($m) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'name_bn' => $m->name_bn,
                'member_no' => $m->member_no,
                'mobile' => $m->mobile,
                'photo_url' => $m->photo_url,
                'area_name' => $m->area?->name,
                'url' => route('members.show', $m, false),
                'savings_accounts' => $m->savingsAccounts->pluck('account_no')->take(3)->values(),
                'loan_accounts' => $m->loans->pluck('loan_no')->take(3)->values(),
                'has_overdue' => $m->loans->contains('status', 'overdue'),
            ];
        }));
    }

    public function create()
    {
        $areas = auth()->user()->isFieldOfficer()
            ? Area::with('fieldOfficers')->active()->whereIn('id', auth()->user()->officerAreaIds())->orderBy('name')->get()
            : Area::with('fieldOfficers')->active()->orderBy('name')->get();

        return view('members.form', [
            'member' => new Member,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['member_no'] = app(AccountNumberService::class)->nextMemberNumber();
        $data['created_by'] = auth()->id();

        // Auto-assign Field Officer from the selected Area
        if (!empty($data['area_id'])) {
            $area = Area::with('fieldOfficers')->find($data['area_id']);
            $assignedOfficer = $area?->fieldOfficers()->first();
            if ($assignedOfficer) {
                $data['field_officer_id'] = $assignedOfficer->id;
            } elseif (auth()->user()->isFieldOfficer()) {
                $data['field_officer_id'] = auth()->id();
            }
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('members/photos', 'public');
        }

        $member = Member::create($data);
        $this->syncNominees($member, $request->input('nominees', []));
        AuditLog::record('member.created', $member, [], $member->toArray());

        return redirect()->route('members.show', $member)->with('success', __('Member created successfully.'));
    }

    public function show(Member $member)
    {
        $member->load([
            'area', 'fieldOfficer', 'nominees', 'documents',
            'savingsAccounts.program',
            'loans.product',
        ]);

        return view('members.show', compact('member'));
    }

    public function edit(Member $member)
    {
        $areas = auth()->user()->isFieldOfficer()
            ? Area::with('fieldOfficers')->active()->whereIn('id', auth()->user()->officerAreaIds())->orderBy('name')->get()
            : Area::with('fieldOfficers')->active()->orderBy('name')->get();

        return view('members.form', [
            'member' => $member,
            'areas' => $areas,
        ]);
    }

    public function update(Request $request, Member $member)
    {
        $data = $this->validateData($request);

        // Auto-assign Field Officer from the selected Area
        if (!empty($data['area_id'])) {
            $area = Area::with('fieldOfficers')->find($data['area_id']);
            $assignedOfficer = $area?->fieldOfficers()->first();
            if ($assignedOfficer) {
                $data['field_officer_id'] = $assignedOfficer->id;
            } elseif (auth()->user()->isFieldOfficer()) {
                $data['field_officer_id'] = auth()->id();
            }
        }

        $before = $member->toArray();
        if ($request->hasFile('photo')) {
            if ($member->photo_path) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('members/photos', 'public');
        }

        $member->update($data);
        $this->syncNominees($member, $request->input('nominees', []));
        AuditLog::record('member.updated', $member, $before, $member->toArray());

        return redirect()->route('members.show', $member)->with('success', __('Member updated successfully.'));
    }

    public function destroy(Member $member)
    {
        if ($member->savingsAccounts()->exists() || $member->loans()->exists()) {
            return back()->with('error', __('Member cannot be deleted because they have financial accounts.'));
        }

        AuditLog::record('member.deleted', $member, $member->toArray(), []);
        $member->delete();

        return redirect()->route('members.index')->with('success', __('Member deleted successfully.'));
    }

    public function statement(Member $member)
    {
        $this->authorize('view', $member);

        $member->load(['savingsAccounts', 'loans']);

        $savingsTxns = $member->savingsAccounts->flatMap->transactions
            ->filter(fn ($t) => $t->status === 'posted')
            ->map(function ($t) {
                $t->ledger_date = $t->txn_date;
                $t->ledger_account = $t->account->account_no;
                $t->ledger_type = __('Savings ' . ucfirst($t->type));
                $t->ledger_debit = in_array($t->type, ['withdrawal', 'account_closing']) ? $t->amount : 0;
                $t->ledger_credit = in_array($t->type, ['deposit', 'account_opening', 'adjustment']) ? $t->amount : 0;
                $t->ledger_balance = $t->balance_after;

                return $t;
            });

        $loanTxns = $member->loans->flatMap->transactions
            ->filter(fn ($t) => $t->status === 'posted')
            ->map(function ($t) {
                $t->ledger_date = $t->txn_date;
                $t->ledger_account = $t->loan->loan_no;
                $t->ledger_type = __('Loan ' . ucfirst($t->type));
                $t->ledger_debit = $t->type === 'disbursement' ? $t->amount : 0;
                $t->ledger_credit = $t->type === 'repayment' ? $t->amount : 0;
                $t->ledger_balance = null;

                return $t;
            });

        $ledger = $savingsTxns->concat($loanTxns)->sortBy('ledger_date')->values();

        return view('members.statement', compact('member', 'ledger'));
    }

    public function statementPdf(Member $member)
    {
        $this->authorize('view', $member);

        $member->load(['savingsAccounts', 'loans']);

        $savingsTxns = $member->savingsAccounts->flatMap->transactions
            ->filter(fn ($t) => $t->status === 'posted')
            ->map(function ($t) {
                $t->ledger_date = $t->txn_date;
                $t->ledger_account = $t->account->account_no;
                $t->ledger_type = __('Savings ' . ucfirst($t->type));
                $t->ledger_debit = in_array($t->type, ['withdrawal', 'account_closing']) ? $t->amount : 0;
                $t->ledger_credit = in_array($t->type, ['deposit', 'account_opening', 'adjustment']) ? $t->amount : 0;
                $t->ledger_balance = $t->balance_after;

                return $t;
            });

        $loanTxns = $member->loans->flatMap->transactions
            ->filter(fn ($t) => $t->status === 'posted')
            ->map(function ($t) {
                $t->ledger_date = $t->txn_date;
                $t->ledger_account = $t->loan->loan_no;
                $t->ledger_type = __('Loan ' . ucfirst($t->type));
                $t->ledger_debit = $t->type === 'disbursement' ? $t->amount : 0;
                $t->ledger_credit = $t->type === 'repayment' ? $t->amount : 0;
                $t->ledger_balance = null;

                return $t;
            });

        $ledger = $savingsTxns->concat($loanTxns)->sortBy('ledger_date')->values();

        return PdfService::streamView('members.statement_pdf', compact('member', 'ledger'), "statement-{$member->member_no}.pdf");
    }

    public function storeDocument(Request $request, Member $member)
    {
        $this->authorize('update', $member);

        $request->validate([
            'type' => 'required|string|max:50',
            'title' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $path = $request->file('file')->store("members/{$member->member_no}/documents", 'public');

        MemberDocument::create([
            'member_id' => $member->id,
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'file_path' => $path,
        ]);

        return back()->with('success', __('Document uploaded successfully.'));
    }

    public function destroyDocument(MemberDocument $document)
    {
        $member = $document->member;
        if ($member) {
            $this->authorize('update', $member);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', __('Document deleted successfully.'));
    }

    public function storeNominee(Request $request, Member $member)
    {
        $this->authorize('update', $member);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'nullable|string|max:255',
            'nid' => 'nullable|string|max:30',
            'mobile' => 'nullable|string|max:20',
            'percentage' => 'nullable|numeric|min:0|max:100',
        ], [], [
            'name' => __('Nominee name'),
            'relationship' => __('Relation'),
            'nid' => __('NID'),
            'mobile' => __('Mobile'),
            'percentage' => __('Percentage'),
        ]);

        $nominee = $member->nominees()->create($data);
        AuditLog::record('member.nominee_added', $member, [], $nominee->toArray());

        return back()->with('success', __('Nominee added successfully.'));
    }

    public function destroyNominee(MemberNominee $nominee)
    {
        $member = $nominee->member;
        if ($member) {
            $this->authorize('update', $member);
        }

        $nomineeData = $nominee->toArray();
        $nominee->delete();
        if ($member) {
            AuditLog::record('member.nominee_deleted', $member, $nomineeData, []);
        }

        return back()->with('success', __('Nominee deleted successfully.'));
    }

    protected function validateData(Request $request): array
    {
        $areaRule = 'nullable|exists:areas,id';
        if (auth()->user()->isFieldOfficer()) {
            $officerAreaIds = implode(',', auth()->user()->officerAreaIds());
            $areaRule = "required|in:{$officerAreaIds}";
        }

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

        return $request->validate([
            'membership_date' => 'required|date',
            'name' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'father_husband_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'gender' => 'required|in:male,female,other',
            'mobile' => 'nullable|string|max:20',
            'nid' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'area_id' => $areaRule,
            'field_officer_id' => 'nullable|exists:users,id',
            'occupation' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,suspended,closed',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'nominees' => 'nullable|array',
            'nominees.*.name' => 'required|string|max:255',
            'nominees.*.relationship' => 'nullable|string|max:255',
            'nominees.*.nid' => 'nullable|string|max:30',
            'nominees.*.mobile' => 'nullable|string|max:20',
            'nominees.*.percentage' => 'nullable|numeric|min:0|max:100',
        ], [], [
            'nominees.*.name' => __('Nominee name'),
            'nominees.*.relationship' => __('Relation'),
            'nominees.*.nid' => __('NID'),
            'nominees.*.mobile' => __('Mobile'),
            'nominees.*.percentage' => __('Percentage'),
        ]);
    }

    protected function syncNominees(Member $member, ?array $nominees): void
    {
        $member->nominees()->delete();

        foreach ($nominees ?? [] as $nominee) {
            if (! empty($nominee['name'])) {
                $member->nominees()->create($nominee);
            }
        }
    }
}