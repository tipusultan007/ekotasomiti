<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsWithdrawal extends Model
{
    protected $fillable = [
        'request_no', 'savings_account_id', 'member_id', 'requested_amount',
        'purpose', 'requested_by', 'requested_at', 'status', 'approved_by',
        'approved_at', 'approved_amount', 'paid_by', 'paid_at', 'payment_method', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
        ];
    }

    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class, 'savings_account_id');
    }

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function requester(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}