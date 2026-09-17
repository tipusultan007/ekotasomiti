<?php

namespace App\Models;

use App\Models\Area;
use App\Models\Member;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'user_area');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'field_officer_id');
    }

    public function savingsAccounts(): HasMany
    {
        return $this->hasMany(SavingsAccount::class, 'field_officer_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'field_officer_id');
    }

    public function officerAreaIds(): array
    {
        if ($this->hasAnyRole(['admin', 'super_admin'])) {
            return \App\Models\Area::pluck('id')->all();
        }

        if (!$this->hasRole('field_officer')) {
            return [];
        }

        return $this->areas()->pluck('areas.id')->all();
    }

    public function isFieldOfficer(): bool
    {
        return $this->hasRole('field_officer');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfficers($query)
    {
        return $query->role('field_officer');
    }
}