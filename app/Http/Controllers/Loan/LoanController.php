<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Loan;
use App\Models\LoanTransaction;
use App\Services\LoanRepaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        Loan::refreshOverdueStatus();

        $query = Loan::with(['member', 'product', 'area', 'fieldOfficer'])
            ->whereIn('status', ['disbursed', 'active', 'overdue']);

        if (auth()->user()->isFieldOfficer()) {
            $query->whereIn('area_id', auth()->user()->officerAreaIds());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('loan_no', 'like', "%{$request->input('search')}%")
                    ->orWhereHas('member', fn ($m) => $m->where('name', 'like', "%{$request->input('search')}%"));
            });
        }

        $loans = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $statsScope = Loan::query();
        if (auth()->user()->isFieldOfficer()) {
            $statsScope->whereIn('area_id', auth()->user()->officerAreaIds());
        }

        $stats = [
            'totalOutstanding' => (clone $statsScope)->whereIn('status', ['disbursed', 'active', 'overdue'])->sum('outstanding'),
            'totalDisbursed' => (clone $statsScope)->whereIn('status', ['disbursed', 'active', 'overdue'])->count(),
            'totalOverdue' => (clone $statsScope)->where('status', 'overdue')->count(),
        ];

        return view('loans.index', compact('loans', 'stats'));
    }

    public function overdue()
    {
        Loan::refreshOverdueStatus();

        $query = Loan::with(['member', 'product', 'area'])
            ->where('status', 'overdue');

        if (auth()->user()->isFieldOfficer()) {
            $query->whereIn('area_id', auth()->user()->officerAreaIds());
        }

        $loans = $query->orderByDesc('id')->paginate(20);

        return view('loans.overdue', compact('loans'));
    }

    public function show(Loan $loan)
    {
        $this->authorize('view', $loan);

        $loan->load([
            'member', 'product', 'area', 'fieldOfficer', 'application',
            'schedules', 'transactions.receiver', 'disbursements.disburser',
        ]);

        if (in_array($loan->status, ['disbursed', 'active'])
            && $loan->schedules->contains(fn ($s) => in_array($s->status, ['due', 'partial']) && $s->due_date->lt(now()))) {
            $loan->update(['status' => 'overdue']);
        }

        return view('loans.show', compact('loan'));
    }

    public function schedule(Loan $loan)
    {
        $this->authorize('view', $loan);
        $loan->load(['member', 'product', 'schedules']);

        return view('loans.schedule', compact('loan'));
    }

    public function repayForm(Loan $loan)
    {
        $this->authorize('view', $loan);
        $this->authorize('collect repayments');

        $loan->load(['member', 'product', 'schedules']);

        $nextDue = $loan->schedules->whereIn('status', ['due', 'partial', 'overdue'])->first();

        $previousDue = $loan->schedules
            ->whereIn('status', ['due', 'partial', 'overdue'])
            ->where('due_date', '<', now()->toDateString())
            ->sum('total');

        return view('loans.repay', compact('loan', 'nextDue', 'previousDue'));
    }

    public function repay(Request $request, Loan $loan)
    {
        $this->authorize('view', $loan);
        $this->authorize('collect repayments');

        $data = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'collection_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'notes' => 'nullable|string',
        ]);

        // Concurrency Guard 1: Atomic cache lock
        $lockKey = "loan_repay_lock_{$loan->id}_" . (auth()->id() ?? 'guest');
        $lock = Cache::lock($lockKey, 5);

        if (! $lock->get()) {
            return back()->with('warning', __('A repayment for this loan is already being recorded. Please wait a moment.'));
        }

        // Concurrency Guard 2: Recent duplicate check within last 4 seconds
        $recentDuplicate = LoanTransaction::where('loan_id', $loan->id)
            ->where('type', 'repayment')
            ->where('amount', $data['amount'])
            ->where('collection_date', $data['collection_date'])
            ->where('created_at', '>=', now()->subSeconds(4))
            ->first();

        if ($recentDuplicate) {
            $lock->release();
            return redirect()->route('savings.receipts.show', ['type' => 'loan', 'id' => $recentDuplicate->id])
                ->with('warning', __('Repayment of :amount was already recorded just now. Duplicate submission prevented.', [
                    'amount' => '৳' . number_format($data['amount'], 2),
                ]));
        }

        try {
            $txn = app(LoanRepaymentService::class)->collect($loan, $data);
        } catch (\RuntimeException $e) {
            $lock->release();
            return back()->with('error', $e->getMessage());
        }

        AuditLog::record('loan_repayment.created', $txn, [], $txn->toArray());

        return redirect()->route('savings.receipts.show', ['type' => 'loan', 'id' => $txn->id])
            ->with('success', __('Repayment recorded successfully. Receipt: :no', ['no' => $txn->txn_no]));
    }

    public function repayments(Request $request)
    {
        $query = LoanTransaction::with(['member', 'loan', 'receiver', 'fieldOfficer'])
            ->where('type', 'repayment')
            ->where('status', 'posted');

        if (auth()->user()->isFieldOfficer()) {
            $query->where(function ($q) {
                $q->where('field_officer_id', auth()->id())
                    ->orWhere('received_by', auth()->id());
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('txn_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('txn_date', '<=', $request->input('date_to'));
        }

        $transactions = $query->orderByDesc('txn_date')->paginate(20)->withQueryString();

        return view('loans.repayments', compact('transactions'));
    }

    public function reverseRepayment(LoanTransaction $transaction)
    {
        $this->authorize('reverse transactions');

        if ($transaction->type !== 'repayment') {
            return back()->with('error', __('Only repayment transactions can be deleted here.'));
        }

        try {
            app(LoanRepaymentService::class)->delete($transaction);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Repayment deleted successfully. Loan balances and cash entries were restored.'));
    }

    public function collectionSheet(Loan $loan)
    {
        $this->authorize('view', $loan);

        $loan->load(['member', 'product', 'schedules', 'fieldOfficer']);

        return view('loans.collection_sheet', compact('loan'));
    }

    public function edit(Loan $loan)
    {
        $this->authorize('update', $loan);

        $loan->load(['member', 'product', 'area', 'fieldOfficer', 'schedules', 'transactions']);

        $areas = auth()->user()->isFieldOfficer()
            ? \App\Models\Area::active()->whereIn('id', auth()->user()->officerAreaIds())->orderBy('name')->get()
            : \App\Models\Area::active()->orderBy('name')->get();
        $officers = \App\Models\User::officers()->active()->orderBy('name')->get();
        $products = \App\Models\LoanProduct::where('status', 'active')->orWhere('id', $loan->loan_product_id)->get();
        $hasRepayments = $loan->transactions()->where('type', 'repayment')->exists() || (float) $loan->total_paid > 0;

        return view('loans.edit', compact('loan', 'areas', 'officers', 'products', 'hasRepayments'));
    }

    public function update(Request $request, Loan $loan)
    {
        $this->authorize('update', $loan);

        $hasRepayments = $loan->transactions()->where('type', 'repayment')->exists() || (float) $loan->total_paid > 0;

        $areaRule = 'nullable|exists:areas,id';
        if (auth()->user()->isFieldOfficer()) {
            $officerAreaIds = implode(',', auth()->user()->officerAreaIds());
            $areaRule = "required|in:{$officerAreaIds}";
        }

        $rules = [
            'area_id' => $areaRule,
            'field_officer_id' => 'nullable|exists:users,id',
            'disbursement_date' => 'required|date',
            'first_due_date' => 'nullable|date',
            'status' => 'required|in:approved,disbursed,active,overdue,completed,written_off,cancelled',
        ];

        if (! $hasRepayments) {
            $rules['principal_amount'] = 'required|numeric|gt:0';
            $rules['interest_rate'] = 'required|numeric|gte:0';
            $rules['term'] = 'required|integer|gt:0';
        }

        $data = $request->validate($rules);
        $before = $loan->toArray();

        if (empty($data['field_officer_id']) && !empty($data['area_id'])) {
            $area = \App\Models\Area::find($data['area_id']);
            $data['field_officer_id'] = $area?->fieldOfficers()->first()?->id ?? $loan->field_officer_id;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($loan, $data, $hasRepayments) {
            $scheduleService = app(\App\Services\LoanScheduleService::class);
            $cashService = app(\App\Services\CashTransactionService::class);

            if (! $hasRepayments) {
                $principal = (float) $data['principal_amount'];
                $rate = (float) $data['interest_rate'];
                $term = (int) $data['term'];
                $disbursementDate = $data['disbursement_date'];
                $firstDueDate = $data['first_due_date'] ?? null;

                $loan->fill([
                    'area_id' => $data['area_id'] ?? null,
                    'field_officer_id' => $data['field_officer_id'] ?? null,
                    'principal_amount' => $principal,
                    'interest_rate' => $rate,
                    'term' => $term,
                    'disbursement_date' => $disbursementDate,
                    'first_due_date' => $firstDueDate,
                    'status' => $data['status'],
                ]);

                // Delete existing schedules and build fresh ones
                $loan->schedules()->delete();
                $scheduleService->buildSchedule($loan);

                // Update disbursement record if exists
                $disbursement = $loan->disbursements()->first();
                if ($disbursement) {
                    $disbursement->update([
                        'amount' => $principal,
                    ]);
                }

                // Update disbursement loan transaction if exists
                $disbursementTxn = $loan->transactions()->where('type', 'disbursement')->first();
                if ($disbursementTxn) {
                    $disbursementTxn->update([
                        'amount' => $principal,
                        'txn_date' => $disbursementDate,
                        'collection_date' => $disbursementDate,
                        'field_officer_id' => $loan->field_officer_id,
                        'area_id' => $loan->area_id,
                    ]);
                }

                // Update cash transaction amount
                $cashService->updateSourceAmount($loan, $principal);
            } else {
                $loan->update([
                    'area_id' => $data['area_id'] ?? null,
                    'field_officer_id' => $data['field_officer_id'] ?? null,
                    'disbursement_date' => $data['disbursement_date'],
                    'first_due_date' => $data['first_due_date'] ?? $loan->first_due_date,
                    'status' => $data['status'],
                ]);
            }
        });

        AuditLog::record('loan.updated', $loan, $before, $loan->fresh()->toArray());

        return redirect()->route('loans.show', $loan)->with('success', __('Loan :no updated successfully.', ['no' => $loan->loan_no]));
    }

    public function destroy(Loan $loan)
    {
        $this->authorize('delete', $loan);

        $loanNo = $loan->loan_no;
        $before = $loan->toArray();
        $cashService = app(\App\Services\CashTransactionService::class);

        \Illuminate\Support\Facades\DB::transaction(function () use ($loan, $cashService) {
            // 1. Collect all loan transaction IDs
            $txnIds = $loan->transactions()->pluck('id')->all();

            // 2. Delete all cash transactions linked to these loan transactions
            if (! empty($txnIds)) {
                $cashTxns = \App\Models\CashTransaction::where('source_type', \App\Models\LoanTransaction::class)
                    ->whereIn('source_id', $txnIds)
                    ->where('status', 'posted')
                    ->get();

                foreach ($cashTxns as $cashTxn) {
                    $cashService->delete($cashTxn);
                }
            }

            // 3. Delete all cash transactions linked directly to the loan (disbursement cash transactions)
            $loanCashTxns = \App\Models\CashTransaction::where('source_type', \App\Models\Loan::class)
                ->where('source_id', $loan->id)
                ->where('status', 'posted')
                ->get();

            foreach ($loanCashTxns as $cashTxn) {
                $cashService->delete($cashTxn);
            }

            // 4. Delete any remaining cash transactions referencing this loan_no
            $refCashTxns = \App\Models\CashTransaction::where('reference', $loan->loan_no)
                ->where('status', 'posted')
                ->get();

            foreach ($refCashTxns as $cashTxn) {
                $cashService->delete($cashTxn);
            }

            // 5. Delete repayment schedules and repayments
            $repayments = \App\Models\LoanRepayment::where('loan_id', $loan->id)->get();
            foreach ($repayments as $repayment) {
                $repayment->schedules()->detach();
                $repayment->delete();
            }

            // 6. Delete disbursements
            $loan->disbursements()->delete();

            // 7. Delete transactions
            $loan->transactions()->delete();

            // 8. Delete schedules
            $loan->schedules()->delete();

            // 9. If loan has an application, revert application status to approved
            if ($loan->application_id && $loan->application) {
                $loan->application->update(['status' => 'approved']);
            }

            // 10. Delete the loan
            $loan->delete();
        });

        AuditLog::record('loan.deleted', $loan, $before, []);

        return redirect()->route('loans.index')->with('success', __('Loan :no and all associated records deleted successfully.', ['no' => $loanNo]));
    }

    public function writeOff(Request $request, Loan $loan)
    {
        $this->authorize('writeOff', $loan);

        $data = $request->validate([
            'write_off_amount' => 'required|numeric|gt:0',
            'write_off_reason' => 'required|string|max:255',
        ]);

        if (! in_array($loan->status, ['disbursed', 'active', 'overdue'])) {
            return back()->with('error', __('Only active or overdue loans can be written off.'));
        }

        $before = $loan->toArray();

        $loan->update([
            'status' => 'written_off',
            'written_off_amount' => $data['write_off_amount'],
            'write_off_reason' => $data['write_off_reason'],
            'written_off_by' => auth()->id(),
            'written_off_at' => now(),
            'closed_by' => auth()->id(),
            'closed_at' => now(),
            'closing_reason' => 'Loan written off',
        ]);

        AuditLog::record('loan.written_off', $loan, $before, $loan->toArray());

        return redirect()->route('loans.show', $loan)->with('success', __('Loan :no written off.', ['no' => $loan->loan_no]));
    }
}