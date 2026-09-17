<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_no' => $this->member_no,
            'name' => $this->name,
            'name_bn' => $this->name_bn,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'husband_name' => $this->husband_name,
            'mobile' => $this->mobile,
            'nid' => $this->nid,
            'dob' => $this->dob?->toDateString(),
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'occupation' => $this->occupation,
            'address' => $this->address,
            'status' => $this->status,
            'area' => $this->whenLoaded('area', fn () => [
                'id' => $this->area->id,
                'name' => $this->area->name,
                'code' => $this->area->code,
            ]),
            'field_officer' => $this->whenLoaded('fieldOfficer', fn () => [
                'id' => $this->fieldOfficer->id,
                'name' => $this->fieldOfficer->name,
            ]),
            'savings_accounts_count' => $this->whenCounted('savingsAccounts'),
            'loans_count' => $this->whenCounted('loans'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
