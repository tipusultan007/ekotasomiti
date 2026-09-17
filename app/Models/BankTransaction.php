<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    protected $fillable = [
        'txn_no', 'bank_account_id', 'type', 'direction', 'amount',
        'txn_date', 'reference', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'txn_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}