<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SettlementRequest;
use App\Models\AuditLog;
use App\Models\FieldOfficerSettlement;
use App\Models\User;
use App\Services\CashSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FieldOfficerSettlement::with(['officer:id,name', 'receiver:id,name'])
            ->where('field_officer_id', $request->user()->id);

        if ($request->filled('date')) {
            $query->whereDate('settlement_date', $request->input('date'));
        }

        $settlements = $query->orderByDesc('settlement_date')
            ->paginate($request->input('per_page', 20));

        return response()->json($settlements);
    }

    public function preview(Request $request): JsonResponse
    {
        $officer = $request->user();
        $date = $request->input('date', now()->toDateString());

        $savings = (float) \App\Models\SavingsTransaction::where('status', 'posted')
            ->whereIn('type', ['deposit', 'account_opening'])
            ->whereDate('txn_date', $date)
            ->where(fn ($q) => $q->where('field_officer_id', $officer->id)->orWhere('received_by', $officer->id))
            ->sum('amount');

        $loans = (float) \App\Models\LoanTransaction::where('status', 'posted')
            ->where('type', 'repayment')
            ->whereDate('txn_date', $date)
            ->where(fn ($q) => $q->where('field_officer_id', $officer->id)->orWhere('received_by', $officer->id))
            ->sum('amount');

        $total = $savings + $loans;

        return response()->json([
            'date' => $date,
            'savings_collection' => $savings,
            'loan_collection' => $loans,
            'other_collection' => 0.0,
            'total_collection' => $total,
            'recommended_cash_submission' => $total,
        ]);
    }

    public function show(Request $request, FieldOfficerSettlement $settlement): JsonResponse
    {
        abort_unless($settlement->field_officer_id === $request->user()->id, 403);

        $settlement->load(['officer:id,name', 'receiver:id,name']);

        return response()->json($settlement);
    }

    public function store(SettlementRequest $request): JsonResponse
    {
        $data = $request->validated();

        $officer = $request->user();

        $settlement = app(CashSettlementService::class)->create($officer, $data);
        AuditLog::record('settlement.created', $settlement, [], $settlement->toArray());

        return response()->json([
            'message' => 'Settlement submitted successfully.',
            'settlement' => [
                'id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'status' => $settlement->status,
            ],
        ], 201);
    }
}
