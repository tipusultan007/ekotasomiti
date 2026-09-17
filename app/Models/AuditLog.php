<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 'before', 'after',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        ?Model $model = null,
        array $before = [],
        array $after = []
    ): self {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? $model::class : null,
            'model_id' => $model?->id,
            'before' => $before ?: null,
            'after' => $after ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}