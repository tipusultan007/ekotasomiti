<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = [
        'name', 'description', 'status',
    ];

    public function expenses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
