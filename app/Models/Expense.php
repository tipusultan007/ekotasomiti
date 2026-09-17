<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'expense_no', 'expense_date', 'expense_category_id', 'amount', 'payment_method',
        'payee', 'description', 'bank_account_id', 'created_by', 'status',
        'reversed_txn_id',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
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