<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsAccount extends Model
{
    protected $fillable = [
        'account_no', 'member_id', 'savings_program_id', 'area_id', 'field_officer_id',
        'opening_date', 'opening_balance', 'current_balance', 'min_deposit',
        'expected_deposit', 'status', 'closed_date', 'closing_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_date' => 'date',
            'closed_date' => 'date',
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'min_deposit' => 'decimal:2',
            'expected_deposit' => 'decimal:2',
        ];
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

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SavingsTransaction::class);
    }

    public function withdrawals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SavingsWithdrawal::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}