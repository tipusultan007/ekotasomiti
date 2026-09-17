<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavingsAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_no' => $this->account_no,
            'status' => $this->status,
            'current_balance' => (float) $this->current_balance,
            'expected_deposit' => (float) $this->expected_deposit,
            'total_deposited' => (float) $this->total_deposited,
            'frequency' => $this->frequency,
            'opened_date' => $this->opened_date?->toDateString(),
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'name' => $this->member->name,
                'member_no' => $this->member->member_no,
            ]),
            'program' => $this->whenLoaded('program', fn () => [
                'id' => $this->program->id,
                'name' => $this->program->name,
                'code' => $this->program->code,
                'min_deposit' => (float) $this->program->min_deposit,
            ]),
            'area' => $this->whenLoaded('area', fn () => [
                'id' => $this->area->id,
                'name' => $this->area->name,
            ]),
        ];
    }
}
