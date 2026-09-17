<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    protected $fillable = [
        'loan_id', 'transaction_id', 'amount', 'principal_paid', 'interest_paid',
        'late_fee', 'collection_date',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'amount' => 'decimal:2',
            'principal_paid' => 'decimal:2',
            'interest_paid' => 'decimal:2',
            'late_fee' => 'decimal:2',
        ];
    }

    public function loan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LoanTransaction::class, 'transaction_id');
    }

    public function schedules(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(LoanSchedule::class, 'loan_repayment_schedule')
            ->withPivot('principal_paid', 'interest_paid', 'late_fee');
    }
}