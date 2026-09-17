<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeCategory extends Model
{
    protected $fillable = [
        'name', 'description', 'status',
    ];

    public function incomes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
