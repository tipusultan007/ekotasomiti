<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'member_no', 'membership_date', 'name', 'name_bn', 'father_husband_name',
        'mother_name', 'dob', 'gender', 'mobile', 'nid', 'address',
        'area_id', 'field_officer_id', 'occupation', 'photo_path', 'status',
        'notes', 'created_by',
    ];

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'membership_date' => 'date',
            'dob' => 'date',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }

    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function fieldOfficer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'field_officer_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function nominees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MemberNominee::class);
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MemberDocument::class);
    }

    public function savingsAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SavingsAccount::class);
    }

    public function loanApplications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanApplication::class);
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