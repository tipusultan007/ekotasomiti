<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settlement_date' => 'required|date|before_or_equal:today',
            'savings_collection' => 'nullable|numeric|min:0',
            'loan_collection' => 'nullable|numeric|min:0',
            'other_collection' => 'nullable|numeric|min:0',
            'cash_submitted' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
