<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    protected $fillable = [
        'loan_id', 'installment_no', 'due_date', 'principal', 'interest',
        'total', 'paid', 'late_fee', 'status', 'paid_at', 'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'principal' => 'decimal:2',
            'interest' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'late_fee' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function loan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function loanRepayments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(LoanRepayment::class, 'loan_repayment_schedule')
            ->withPivot('principal_paid', 'interest_paid', 'late_fee');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['due', 'partial', 'overdue']);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }
}