<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    protected $fillable = [
        'income_no', 'income_date', 'income_category_id', 'amount', 'payment_method',
        'source', 'description', 'bank_account_id', 'created_by', 'status',
        'reversed_txn_id',
    ];

    protected function casts(): array
    {
        return [
            'income_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    public function bankAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}