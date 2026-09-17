<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'installment_no' => (int) $this->installment_no,
            'due_date' => $this->due_date?->toDateString(),
            'principal' => (float) $this->principal,
            'interest' => (float) $this->interest,
            'total' => (float) $this->total,
            'paid' => (float) $this->paid,
            'status' => $this->status,
        ];
    }
}
