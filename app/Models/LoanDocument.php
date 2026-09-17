<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanDocument extends Model
{
    protected $fillable = [
        'loan_application_id',
        'loan_id',
        'type',
        'title',
        'file_path',
        'notes',
        'created_by',
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function application(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function loan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
