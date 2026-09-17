<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    protected $fillable = [
        'code', 'name', 'frequency', 'prefix', 'min_amount', 'max_amount',
        'interest_rate', 'interest_type', 'processing_fee', 'insurance_fee',
        'min_term', 'max_term', 'status', 'description',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'insurance_fee' => 'decimal:2',
        ];
    }

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function applications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}