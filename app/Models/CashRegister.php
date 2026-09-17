<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    protected $fillable = [
        'register_date', 'opening_balance', 'closing_balance', 'total_in',
        'total_out', 'opened_by', 'opened_at', 'closed_by', 'closed_at',
        'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'register_date' => 'date',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'total_in' => 'decimal:2',
            'total_out' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function opener(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}