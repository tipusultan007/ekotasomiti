<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'account_name', 'account_number', 'bank_name', 'branch',
        'opening_balance', 'current_balance', 'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
        ];
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}