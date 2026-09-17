<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldOfficerSettlement extends Model
{
    protected $fillable = [
        'settlement_no', 'field_officer_id', 'settlement_date', 'savings_collection',
        'loan_collection', 'other_collection', 'total_collection', 'cash_submitted',
        'remaining_cash', 'cash_transaction_id', 'received_by', 'received_at',
        'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'savings_collection' => 'decimal:2',
            'loan_collection' => 'decimal:2',
            'other_collection' => 'decimal:2',
            'total_collection' => 'decimal:2',
            'cash_submitted' => 'decimal:2',
            'remaining_cash' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function officer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'field_officer_id');
    }

    public function cashTransaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CashTransaction::class, 'cash_transaction_id');
    }

    public function receiver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}