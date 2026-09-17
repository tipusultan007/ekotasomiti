<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsTransaction extends Model
{
    protected $fillable = [
        'txn_no', 'savings_account_id', 'member_id', 'savings_program_id',
        'type', 'amount', 'txn_date', 'collection_date', 'payment_method',
        'type', 'amount', 'gross_amount', 'fund_amount', 'fund_transaction_id',
        'txn_date', 'collection_date', 'payment_method',
        'field_officer_id', 'area_id', 'received_by', 'reference', 'notes',
        'balance_after', 'status', 'reversed_txn_id',
    ];

    protected function casts(): array
    {
        return [
            'txn_date' => 'date',
            'collection_date' => 'date',
            'amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'fund_amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
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

    public function program(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SavingsProgram::class, 'savings_program_id');
    }

    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function fieldOfficer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'field_officer_id');
    }

    public function receiver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function reversedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SavingsTransaction::class, 'reversed_txn_id');
    }

    public function fundTransaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FundTransaction::class, 'fund_transaction_id');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'deposit' => 'Deposit',
            'withdrawal' => 'Withdrawal',
            'account_opening' => 'Account Opening',
            'account_closing' => 'Account Closing',
            'adjustment' => 'Adjustment',
            'correction' => 'Correction',
            default => ucfirst($type),
        };
    }
}