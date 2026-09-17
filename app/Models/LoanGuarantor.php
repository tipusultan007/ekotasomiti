<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanGuarantor extends Model
{
    protected $fillable = [
        'loan_application_id', 'name', 'relationship', 'nid', 'mobile', 'address',
    ];

    public function application(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }
}