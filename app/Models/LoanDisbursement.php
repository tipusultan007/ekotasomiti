<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanDisbursement extends Model
{
    protected $fillable = [
        'disbursement_no', 'loan_id', 'amount', 'payment_method',
        'bank_account_id', 'disbursed_by', 'disbursed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'disbursed_at' => 'datetime',
        ];
    }

    public function loan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function disburser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }
}