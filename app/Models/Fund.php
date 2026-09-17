<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fund extends Model
{
    protected $fillable = [
        'code',
        'name',
        'current_balance',
        'status',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
        ];
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FundTransaction::class);
    }

    public function savingsPrograms(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SavingsProgram::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

