<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'settlement_no' => $this->settlement_no,
            'settlement_date' => $this->settlement_date?->toDateString(),
            'savings_collection' => (float) $this->savings_collection,
            'loan_collection' => (float) $this->loan_collection,
            'other_collection' => (float) $this->other_collection,
            'total_collection' => (float) $this->total_collection,
            'cash_submitted' => (float) $this->cash_submitted,
            'remaining_cash' => (float) $this->remaining_cash,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
