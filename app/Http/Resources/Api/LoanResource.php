<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_no' => $this->loan_no,
            'principal_amount' => (float) $this->principal_amount,
            'interest_rate' => (float) $this->interest_rate,
            'interest_type' => $this->interest_type,
            'term' => (int) $this->term,
            'frequency' => $this->frequency,
            'installment_amount' => (float) $this->installment_amount,
            'total_interest' => (float) $this->total_interest,
            'total_payable' => (float) $this->total_payable,
            'total_paid' => (float) $this->total_paid,
            'outstanding' => (float) $this->outstanding,
            'status' => $this->status,
            'disbursement_date' => $this->disbursement_date?->toDateString(),
            'first_due_date' => $this->first_due_date?->toDateString(),
            'processing_fee' => (float) $this->processing_fee,
            'insurance_fee' => (float) $this->insurance_fee,
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'name' => $this->member->name,
                'member_no' => $this->member->member_no,
                'mobile' => $this->member->mobile,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'code' => $this->product->code,
                'frequency' => $this->product->frequency,
            ]),
            'area' => $this->whenLoaded('area', fn () => [
                'id' => $this->area->id,
                'name' => $this->area->name,
                'code' => $this->area->code,
            ]),
            'field_officer' => $this->whenLoaded('fieldOfficer', fn () => [
                'id' => $this->fieldOfficer->id,
                'name' => $this->fieldOfficer->name,
            ]),
            'schedules' => LoanScheduleResource::collection($this->whenLoaded('schedules')),
            'transactions' => LoanTransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
