<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberNominee extends Model
{
    protected $fillable = [
        'member_id', 'name', 'relationship', 'nid', 'mobile', 'dob',
        'address', 'percentage', 'priority', 'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'percentage' => 'decimal:2',
        ];
    }

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}