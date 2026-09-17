<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    protected $fillable = [
        'txn_no', 'cash_register_id', 'type', 'direction', 'amount',
        'source_type', 'source_id', 'field_officer_id', 'payment_method',
        'reference', 'notes', 'created_by', 'status', 'reversed_txn_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function register(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function fieldOfficer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'field_officer_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}