<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $officer = $this->user();
        $officerAreaIds = $officer->officerAreaIds();

        return [
            'member_id' => [
                'required',
                Rule::exists('members', 'id')->whereIn('area_id', $officerAreaIds),
            ],
            'loan_product_id' => 'required|exists:loan_products,id',
            'requested_amount' => 'required|numeric|gt:0|max:10000000',
            'requested_term' => 'required|integer|min:1|max:360',
            'purpose' => 'nullable|string|max:255',
            'application_date' => 'required|date|before_or_equal:today',
            'disbursement_date' => 'required|date|before_or_equal:today',
            'first_due_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,bkash,nagad,other',
            'guarantors' => 'nullable|array|max:5',
            'guarantors.*.name' => 'required|string|max:255',
            'guarantors.*.relationship' => 'nullable|string|max:255',
            'guarantors.*.nid' => 'nullable|string|max:30',
            'guarantors.*.mobile' => 'nullable|string|max:20',
            'guarantors.*.address' => 'nullable|string|max:500',
            'documents' => 'nullable|array|max:10',
            'documents.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx|max:10240',
            'document_titles' => 'nullable|array',
            'document_titles.*' => 'nullable|string|max:255',
            'document_types' => 'nullable|array',
            'document_types.*' => 'nullable|string|max:50',
        ];
    }
}
