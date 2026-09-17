<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type', 'attachable_id', 'type', 'title', 'file_path', 'notes',
    ];

    public function attachable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}