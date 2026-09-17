<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Area extends Model
{
    protected $fillable = [
        'code', 'name', 'address', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function fieldOfficers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_area');
    }

    public function members(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function savingsAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SavingsAccount::class);
    }

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}