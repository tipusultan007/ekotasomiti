<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsProgram extends Model
{
    protected $fillable = [
        'code', 'name', 'frequency', 'prefix', 'min_deposit', 'expected_deposit',
        'max_balance', 'status', 'description',
        'max_balance', 'status', 'description', 'fund_id', 'fund_contribution',
    ];

    protected function casts(): array
    {
        return [
            'min_deposit' => 'decimal:2',
            'expected_deposit' => 'decimal:2',
            'max_balance' => 'decimal:2',
            'fund_contribution' => 'decimal:2',
        ];
    }

    public function fund(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function accounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SavingsAccount::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SavingsTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}