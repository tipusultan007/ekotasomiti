<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundTransaction extends Model
{
    protected $fillable = [
        'txn_no',
        'fund_id',
        'member_id',
        'savings_account_id',
        'savings_transaction_id',
        'type',
        'direction',
        'amount',
        'balance_after',
        'txn_date',
        'received_by',
        'created_by',
        'payment_method',
        'reference',
        'notes',
        'status',
        'reversed_txn_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'txn_date' => 'date',
        ];
    }

    public function fund(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function savingsAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class);
    }

    public function savingsTransaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SavingsTransaction::class);
    }

    public function receiver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FundTransaction::class, 'reversed_txn_id');
    }
}

