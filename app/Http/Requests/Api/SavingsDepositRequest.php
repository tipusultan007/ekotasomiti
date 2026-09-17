<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SavingsDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $date = $this->input('txn_date')
            ?? $this->input('collection_date')
            ?? $this->input('payment_date')
            ?? now()->toDateString();

        $this->merge([
            'txn_date' => $date,
            'collection_date' => $date,
        ]);
    }

    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:savings_accounts,id',
            'amount' => 'required|numeric|min:0.01|max:10000000',
            'txn_date' => 'required|date',
            'collection_date' => 'nullable|date',
            'payment_method' => 'nullable|in:cash,bank,bkash,nagad,other',
            'notes' => 'nullable|string|max:255',
        ];
    }
}
