<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanTransaction extends Model
{
    protected $fillable = [
        'txn_no', 'loan_id', 'member_id', 'type', 'amount', 'principal_paid',
        'interest_paid', 'late_fee', 'txn_date', 'collection_date', 'payment_method',
        'field_officer_id', 'area_id', 'received_by', 'reference', 'notes',
        'status', 'reversed_txn_id',
    ];

    protected function casts(): array
    {
        return [
            'txn_date' => 'date',
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

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function fieldOfficer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'field_officer_id');
    }

    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function receiver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}