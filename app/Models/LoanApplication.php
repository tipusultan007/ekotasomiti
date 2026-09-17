<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanApplication extends Model
{
    protected $fillable = [
        'application_no', 'member_id', 'loan_product_id', 'requested_amount',
        'requested_term', 'installment_amount', 'purpose', 'area_id', 'field_officer_id', 'application_date',
        'status', 'verification_info', 'guarantor_info', 'documents_info', 'remarks',
        'created_by', 'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at',
        'approved_amount', 'approved_interest_rate', 'approved_term',
        'approved_installment', 'approval_remarks', 'rejected_by', 'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'application_date' => 'date',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'requested_amount' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'approved_interest_rate' => 'decimal:2',
            'approved_installment' => 'decimal:2',
        ];
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

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function guarantors(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanGuarantor::class);
    }

    public function loan(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Loan::class, 'application_id');
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanDocument::class);
    }

    public function scopeReadyToDisburse($query)
    {
        return $query->where('status', 'approved');
    }
}