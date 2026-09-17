<?php

namespace App\Http\Controllers\Collection;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FieldOfficerSettlement;
use App\Models\User;
use App\Services\CashSettlementService;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(FieldOfficerSettlement::class, 'settlement');
    }

    public function index(Request $request)
    {
        $query = FieldOfficerSettlement::with(['officer', 'receiver']);

        if (auth()->user()->isFieldOfficer()) {
            $query->where('field_officer_id', auth()->id());
        }

        if ($request->filled('date')) {
            $query->whereDate('settlement_date', $request->input('date'));
        }

        $settlements = $query->orderByDesc('settlement_date')->paginate(20)->withQueryString();

        return view('collection.settlements.index', compact('settlements'));
    }

    public function create()
    {
        $officers = auth()->user()->isFieldOfficer()
            ? User::where('id', auth()->id())->get()
            : User::officers()->active()->orderBy('name')->get();

        $defaultOfficer = auth()->user()->isFieldOfficer() ? auth()->user() : null;

        $totals = $defaultOfficer
            ? app(CashSettlementService::class)->computeTotals($defaultOfficer, now()->toDateString())
            : null;

        return view('collection.settlements.create', compact('officers', 'totals', 'defaultOfficer'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'field_officer_id' => 'required|exists:users,id',
            'settlement_date' => 'required|date',
            'savings_collection' => 'nullable|numeric|min:0',
            'loan_collection' => 'nullable|numeric|min:0',
            'other_collection' => 'nullable|numeric|min:0',
            'cash_submitted' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $officer = User::findOrFail($data['field_officer_id']);

        $settlement = app(CashSettlementService::class)->create($officer, $data);
        AuditLog::record('settlement.created', $settlement, [], $settlement->toArray());

        return redirect()->route('collection.settlements.index')->with('success', __('Settlement submitted successfully.'));
    }

    public function receive(FieldOfficerSettlement $settlement)
    {
        $this->authorize('manage settlements');

        try {
            $settlement = app(CashSettlementService::class)->receive($settlement);
        } catch (\RuntimeException $e) {
            return back()->with('error', __($e->getMessage()));
        }

        AuditLog::record('settlement.received', $settlement, [], $settlement->toArray());

        return back()->with('success', __('Cash received from field officer.'));
    }

    public function destroy(FieldOfficerSettlement $settlement)
    {
        if ($settlement->status === 'received') {
            return back()->with('error', __('A received settlement cannot be deleted.'));
        }

        AuditLog::record('settlement.deleted', $settlement, $settlement->toArray(), []);
        $settlement->delete();

        return redirect()->route('collection.settlements.index')->with('success', __('Settlement deleted.'));
    }
}