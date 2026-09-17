<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoanTransaction;
use App\Models\SavingsTransaction;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $orgName = Setting::get('org_name', config('app.name'));
        $orgAddress = Setting::get('org_address', '');
        $orgPhone = Setting::get('org_phone', '');

        $data = match ($type) {
            'savings' => $this->savings($id, $orgName, $orgAddress, $orgPhone),
            'loan' => $this->loan($id, $orgName, $orgAddress, $orgPhone),
            default => null,
        };

        abort_if(! $data, 404);

        return response()->json($data);
    }

    protected function savings(int $id, string $orgName, string $orgAddress, string $orgPhone): ?array
    {
        $txn = SavingsTransaction::with(['member', 'account.program', 'fieldOfficer', 'receiver'])->find($id);

        if (! $txn) {
            return null;
        }

        return [
            'org_name' => $orgName,
            'org_address' => $orgAddress,
            'org_phone' => $orgPhone,
            'receipt_no' => $txn->txn_no,
            'type' => 'savings',
            'type_label' => SavingsTransaction::typeLabel($txn->type),
            'date' => $txn->txn_date,
            'member_name' => $txn->member->name,
            'member_no' => $txn->member->member_no,
            'account_no' => $txn->account->account_no,
            'program' => $txn->account->program->name,
            'amount' => $txn->amount,
            'payment_method' => $txn->payment_method,
            'field_officer' => $txn->fieldOfficer?->name,
            'received_by' => $txn->receiver?->name,
            'previous_balance' => (float) $txn->balance_after - (float) $txn->amount,
            'balance_after' => $txn->balance_after,
            'reference' => $txn->reference,
            'notes' => $txn->notes,
        ];
    }

    protected function loan(int $id, string $orgName, string $orgAddress, string $orgPhone): ?array
    {
        $txn = LoanTransaction::with(['member', 'loan.product', 'fieldOfficer', 'receiver'])->find($id);

        if (! $txn) {
            return null;
        }

        return [
            'org_name' => $orgName,
            'org_address' => $orgAddress,
            'org_phone' => $orgPhone,
            'receipt_no' => $txn->txn_no,
            'type' => 'loan',
            'type_label' => ucfirst($txn->type),
            'date' => $txn->txn_date,
            'member_name' => $txn->member->name,
            'member_no' => $txn->member->member_no,
            'account_no' => $txn->loan->loan_no,
            'program' => $txn->loan->product->name,
            'amount' => $txn->amount,
            'principal_paid' => $txn->principal_paid,
            'interest_paid' => $txn->interest_paid,
            'late_fee' => $txn->late_fee,
            'payment_method' => $txn->payment_method,
            'field_officer' => $txn->fieldOfficer?->name,
            'received_by' => $txn->receiver?->name,
            'previous_balance' => (float) $txn->loan->outstanding + (float) $txn->amount,
            'balance_after' => $txn->loan->outstanding,
            'reference' => $txn->reference,
            'notes' => $txn->notes,
        ];
    }
}
