<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberDocument;
use App\Services\AccountNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Member::with(['area:id,code,name', 'fieldOfficer:id,name'])
            ->whereIn('area_id', $request->user()->officerAreaIds());

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

        $members = $query->orderBy('name')
            ->paginate($request->input('per_page', 20));

        return response()->json($members);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim($request->input('q', $request->input('search', '')));
        if (strlen($term) < 1) {
            return response()->json([]);
        }

        $query = Member::with([
            'area:id,code,name',
            'fieldOfficer:id,name',
            'savingsAccounts' => fn ($q) => $q->select('id', 'member_id', 'account_no', 'status', 'current_balance', 'savings_program_id')->with('program:id,name,code,frequency')->where('status', 'active'),
            'loans' => fn ($q) => $q->select('id', 'member_id', 'loan_no', 'status', 'outstanding', 'installment_amount', 'loan_product_id')->with('product:id,name,code,frequency')->whereIn('status', ['active', 'disbursed', 'overdue']),
        ])
            ->whereIn('area_id', $request->user()->officerAreaIds());

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

        $members = $query->limit($request->input('limit', 15))->get();

        return response()->json($members->map(function ($m) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'name_bn' => $m->name_bn,
                'member_no' => $m->member_no,
                'mobile' => $m->mobile,
                'nid' => $m->nid,
                'photo_url' => $m->photo_url,
                'status' => $m->status,
                'area' => $m->area ? ['id' => $m->area->id, 'code' => $m->area->code, 'name' => $m->area->name] : null,
                'savings_accounts' => $m->savingsAccounts->map(fn ($s) => [
                    'id' => $s->id,
                    'account_no' => $s->account_no,
                    'program_name' => $s->program->name ?? '',
                    'frequency' => $s->program->frequency ?? '',
                    'current_balance' => (float) $s->current_balance,
                ]),
                'loans' => $m->loans->map(fn ($l) => [
                    'id' => $l->id,
                    'loan_no' => $l->loan_no,
                    'product_name' => $l->product->name ?? '',
                    'frequency' => $l->product->frequency ?? '',
                    'outstanding' => (float) $l->outstanding,
                    'status' => $l->status,
                ]),
                'has_overdue' => $m->loans->contains('status', 'overdue'),
            ];
        }));
    }

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

        $validated = $request->validate([
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
            'area_id' => 'required|exists:areas,id',
            'occupation' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,suspended,closed',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'nominees' => 'nullable|array',
            'nominees.*.name' => 'required|string|max:255',
            'nominees.*.relationship' => 'nullable|string|max:255',
            'nominees.*.nid' => 'nullable|string|max:30',
            'nominees.*.mobile' => 'nullable|string|max:20',
            'nominees.*.percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        abort_unless(in_array((int) $validated['area_id'], $officerAreaIds, true), 403, 'Area not assigned to this field officer.');

        $validated['member_no'] = app(AccountNumberService::class)->nextMemberNumber();
        $validated['created_by'] = $officer->id;
        $validated['field_officer_id'] = $officer->id;
        $validated['status'] = $validated['status'] ?? 'active';

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('members/photos', 'public');
        }

        $member = Member::create($validated);

        if (! empty($request->input('nominees'))) {
            foreach ($request->input('nominees') as $nominee) {
                if (! empty($nominee['name'])) {
                    $member->nominees()->create($nominee);
                }
            }
        }

        AuditLog::record('member.created', $member, [], $member->toArray());

        return response()->json([
            'message' => __('Member registered successfully.'),
            'member' => $member->load(['area:id,code,name', 'fieldOfficer:id,name', 'nominees']),
        ], 201);
    }

    public function show(Request $request, Member $member): JsonResponse
    {
        $areaIds = $request->user()->officerAreaIds();
        abort_unless(in_array($member->area_id, $areaIds), 403);

        $member->load([
            'area:id,code,name',
            'fieldOfficer:id,name',
            'nominees',
            'documents',
            'savingsAccounts' => fn ($q) => $q->with('program:id,name,code,frequency'),
            'loans' => fn ($q) => $q->with('product:id,name,code,frequency')->whereIn('status', ['disbursed', 'active', 'overdue']),
        ]);

        $recentSavingsTxns = \App\Models\SavingsTransaction::where('member_id', $member->id)
            ->where('status', 'posted')
            ->with('account:id,account_no')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'txn_no' => $t->txn_no,
                'type' => $t->type,
                'amount' => $t->amount,
                'date' => $t->txn_date,
                'account_no' => $t->account->account_no ?? null,
                'module' => 'savings',
            ]);

        $recentLoanTxns = \App\Models\LoanTransaction::where('member_id', $member->id)
            ->where('status', 'posted')
            ->with('loan:id,loan_no')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'txn_no' => $t->txn_no,
                'type' => $t->type,
                'amount' => $t->amount,
                'date' => $t->txn_date,
                'loan_no' => $t->loan->loan_no ?? null,
                'module' => 'loan',
            ]);

        $recentTransactions = $recentSavingsTxns->concat($recentLoanTxns)
            ->sortByDesc('date')
            ->take(10)
            ->values();

        return response()->json([
            'member' => $member,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $areaIds = $request->user()->officerAreaIds();
        abort_unless(in_array($member->area_id, $areaIds), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'father_husband_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'mobile' => 'nullable|string|max:20',
            'nid' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'occupation' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,suspended,closed',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('members/photos', 'public');
        }

        $before = $member->toArray();
        $member->update($validated);
        AuditLog::record('member.updated', $member, $before, $member->toArray());

        return response()->json([
            'message' => __('Member updated successfully'),
            'photo_url' => $member->photo_url,
            'member' => $member->fresh(['area', 'fieldOfficer']),
        ]);
    }

    public function uploadPhoto(Request $request, Member $member): JsonResponse
    {
        $areaIds = $request->user()->officerAreaIds();
        abort_unless(in_array($member->area_id, $areaIds), 403);

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
            Storage::disk('public')->delete($member->photo_path);
        }

        $path = $request->file('photo')->store('members/photos', 'public');
        $before = $member->toArray();
        $member->update(['photo_path' => $path]);
        AuditLog::record('member.photo_updated', $member, $before, $member->toArray());

        return response()->json([
            'message' => __('Profile photo updated successfully'),
            'photo_url' => asset('storage/' . $path),
            'photo_path' => $path,
            'member' => $member->fresh(['area', 'fieldOfficer']),
        ]);
    }

    public function documents(Request $request, Member $member): JsonResponse
    {
        $areaIds = $request->user()->officerAreaIds();
        abort_unless(in_array($member->area_id, $areaIds), 403);

        return response()->json([
            'documents' => $member->documents()->latest()->get()->map(fn ($doc) => [
                'id' => $doc->id,
                'type' => $doc->type,
                'title' => $doc->title,
                'file_url' => asset('storage/' . $doc->file_path),
                'file_path' => $doc->file_path,
                'created_at' => $doc->created_at,
            ]),
        ]);
    }

    public function storeDocument(Request $request, Member $member): JsonResponse
    {
        $areaIds = $request->user()->officerAreaIds();
        abort_unless(in_array($member->area_id, $areaIds), 403);

        $request->validate([
            'type' => 'required|in:nid,photo,signature,other',
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $path = $request->file('file')->store("members/{$member->member_no}/documents", 'public');

        $doc = MemberDocument::create([
            'member_id' => $member->id,
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'file_path' => $path,
        ]);

        return response()->json([
            'message' => __('Document uploaded successfully.'),
            'document' => [
                'id' => $doc->id,
                'type' => $doc->type,
                'title' => $doc->title,
                'file_url' => asset('storage/' . $doc->file_path),
                'file_path' => $doc->file_path,
                'created_at' => $doc->created_at,
            ],
        ], 201);
    }

    public function destroyDocument(Request $request, Member $member, MemberDocument $document): JsonResponse
    {
        $areaIds = $request->user()->officerAreaIds();
        abort_unless(in_array($member->area_id, $areaIds), 403);
        abort_unless($document->member_id === $member->id, 404);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'message' => __('Document deleted successfully.'),
        ]);
    }
}
