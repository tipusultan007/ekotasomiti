<?php

namespace App\Http\Controllers\Savings;

use App\Http\Controllers\Controller;
use App\Models\LoanTransaction;
use App\Models\SavingsTransaction;
use App\Services\PdfService;

class ReceiptController extends Controller
{
    public function show(string $type, int $id)
    {
        $data = $this->resolve($type, $id);
        abort_if(! $data, 404);

        return view('receipts.show', $data);
    }

    public function pdf(string $type, int $id)
    {
        $data = $this->resolve($type, $id);
        abort_if(! $data, 404);

        return PdfService::streamView('receipts.pdf', $data, $data['title'] . '.pdf');
    }

    public function showPublic(string $type, int $id)
    {
        $data = $this->resolve($type, $id);
        abort_if(! $data, 404);

        return view('receipts.public', $data);
    }

    protected function resolve(string $type, int $id): ?array
    {
        $orgName = \App\Models\Setting::get('org_name', config('app.name'));
        $orgAddress = \App\Models\Setting::get('org_address', '');
        $orgPhone = \App\Models\Setting::get('org_phone', '');

        return match ($type) {
            'savings' => $this->savings($id, $orgName, $orgAddress, $orgPhone),
            'loan' => $this->loan($id, $orgName, $orgAddress, $orgPhone),
            'withdrawal' => $this->savings($id, $orgName, $orgAddress, $orgPhone, true),
            default => null,
        };
    }

    protected function savings(int $id, string $orgName, string $orgAddress, string $orgPhone, bool $withdrawal = false): array
    {
        $txn = SavingsTransaction::with(['member', 'account.program', 'fieldOfficer', 'receiver'])->find($id);
        $txn = SavingsTransaction::with(['member', 'account.program.fund', 'fundTransaction.fund', 'fieldOfficer', 'receiver'])->find($id);

        if (! $txn) {
            return [];
        }

        if (auth()->check() && auth()->user()->isFieldOfficer()) {
            $user = auth()->user();
            $isOwn = $txn->field_officer_id === $user->id || $txn->received_by === $user->id;
            $isInArea = in_array($txn->area_id, $user->officerAreaIds());
            if (! $isOwn && ! $isInArea) {
                return [];
            }
        }

        $grossAmount = (float) ($txn->gross_amount ?? $txn->amount);
        $fundAmount = (float) ($txn->fund_amount ?? 0);
        $fundName = $txn->fundTransaction?->fund?->name ?? $txn->account?->program?->fund?->name ?? __('Welfare Fund');

        return [
            'orgName' => $orgName,
            'orgAddress' => $orgAddress,
            'orgPhone' => $orgPhone,
            'receiptNo' => $txn->txn_no,
            'title' => __($withdrawal ? 'Withdrawal' : 'Savings Deposit') . ' ' . __('Receipt') . ' - ' . $txn->txn_no,
            'date' => $txn->txn_date,
            'member' => $txn->member,
            'accountNo' => $txn->account->account_no,
            'program' => $txn->account->program->name,
            'amount' => $txn->amount,
            'grossAmount' => $grossAmount,
            'fundAmount' => $fundAmount,
            'fundName' => $fundName,
            'paymentMethod' => $txn->payment_method,
            'fieldOfficer' => $txn->fieldOfficer?->name,
            'receivedBy' => $txn->receiver?->name,
            'previousBalance' => $withdrawal
                ? (float) $txn->balance_after + (float) $txn->amount
                : (float) $txn->balance_after - (float) $txn->amount,
            'balanceAfter' => $txn->balance_after,
            'reference' => $txn->reference,
            'notes' => $txn->notes,
            'typeLabel' => $withdrawal ? 'Withdrawal' : 'Deposit',
        ];
    }

    protected function loan(int $id, string $orgName, string $orgAddress, string $orgPhone): array
    {
        $txn = LoanTransaction::with(['member', 'loan', 'fieldOfficer', 'receiver'])->find($id);

        if (! $txn) {
            return [];
        }

        if (auth()->check() && auth()->user()->isFieldOfficer()) {
            $user = auth()->user();
            $isOwn = $txn->field_officer_id === $user->id || $txn->received_by === $user->id;
            $isInArea = in_array($txn->area_id, $user->officerAreaIds());
            if (! $isOwn && ! $isInArea) {
                return [];
            }
        }

        return [
            'orgName' => $orgName,
            'orgAddress' => $orgAddress,
            'orgPhone' => $orgPhone,
            'receiptNo' => $txn->txn_no,
            'title' => __('Loan') . ' ' . __(ucfirst($txn->type)) . ' ' . __('Receipt') . ' - ' . $txn->txn_no,
            'date' => $txn->txn_date,
            'member' => $txn->member,
            'accountNo' => $txn->loan->loan_no,
            'program' => $txn->loan->product->name,
            'amount' => $txn->amount,
            'paymentMethod' => $txn->payment_method,
            'fieldOfficer' => $txn->fieldOfficer?->name,
            'receivedBy' => $txn->receiver?->name,
            'previousBalance' => (float) $txn->loan->outstanding + (float) $txn->amount,
            'balanceAfter' => $txn->loan->outstanding,
            'reference' => $txn->reference,
            'notes' => $txn->notes,
            'typeLabel' => ucfirst($txn->type),
            'principal' => $txn->principal_paid,
            'interest' => $txn->interest_paid,
            'lateFee' => $txn->late_fee,
        ];
    }
}