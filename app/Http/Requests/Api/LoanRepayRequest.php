<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoanRepayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $date = $this->input('collection_date')
            ?? $this->input('txn_date')
            ?? $this->input('payment_date')
            ?? now()->toDateString();

        $this->merge([
            'collection_date' => $date,
            'txn_date' => $date,
        ]);
    }

    public function rules(): array
    {
        return [
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:0.01|max:10000000',
            'collection_date' => 'required|date',
            'txn_date' => 'nullable|date',
            'payment_method' => 'nullable|in:cash,bank,bkash,nagad,other',
            'notes' => 'nullable|string|max:255',
        ];
    }
}
