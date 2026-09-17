<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSequence extends Model
{
    protected $fillable = ['key', 'prefix', 'last_number', 'length', 'yearly'];

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
            'length' => 'integer',
            'yearly' => 'boolean',
        ];
    }
}