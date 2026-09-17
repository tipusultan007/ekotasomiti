<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberDocument extends Model
{
    protected $fillable = ['member_id', 'type', 'title', 'file_path', 'notes'];

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}