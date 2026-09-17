<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'loan_no', 'application_id', 'member_id', 'loan_product_id', 'area_id',
        'field_officer_id', 'principal_amount', 'interest_rate', 'interest_type',
        'term', 'frequency', 'installment_amount', 'processing_fee', 'insurance_fee',
        'total_interest', 'total_payable', 'total_paid', 'outstanding',
        'disbursement_date', 'first_due_date', 'status', 'disbursed_by',
        'disbursed_at', 'closed_by', 'closed_at', 'closing_reason',
        'written_off_amount', 'write_off_reason', 'written_off_by', 'written_off_at',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'insurance_fee' => 'decimal:2',
            'total_interest' => 'decimal:2',
            'total_payable' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'outstanding' => 'decimal:2',
            'written_off_amount' => 'decimal:2',
            'disbursement_date' => 'date',
            'first_due_date' => 'date',
            'disbursed_at' => 'datetime',
            'closed_at' => 'datetime',
            'written_off_at' => 'datetime',
        ];
    }

    public function application(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'application_id');
    }

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function fieldOfficer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'field_officer_id');
    }

    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanSchedule::class)->orderBy('installment_no');
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanTransaction::class)->orderBy('txn_date');
    }

    public function latestRepayment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LoanTransaction::class)
            ->where('type', 'repayment')
            ->where('status', 'posted')
            ->latestOfMany('txn_date');
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanDocument::class);
    }

    public function disbursements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanDisbursement::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['disbursed', 'active', 'overdue']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public static function refreshOverdueStatus(): int
    {
        return static::whereIn('status', ['disbursed', 'active', 'overdue'])
            ->whereHas('schedules', fn ($q) => $q
                ->whereIn('status', ['due', 'partial'])
                ->where('due_date', '<', now()->toDateString()))
            ->update(['status' => 'overdue']);
    }
}