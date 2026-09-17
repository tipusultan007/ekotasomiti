<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountSequence extends Model
{
    protected $fillable = ['key', 'prefix', 'last_number', 'length'];

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
            'length' => 'integer',
        ];
    }
}