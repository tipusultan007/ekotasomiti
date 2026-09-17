<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'txn_no' => $this->txn_no,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'principal_paid' => (float) $this->principal_paid,
            'interest_paid' => (float) $this->interest_paid,
            'late_fee' => (float) $this->late_fee,
            'date' => $this->txn_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }
}
