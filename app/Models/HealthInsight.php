<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthInsight extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'locale',
        'summary',
        'items',
        'context_snapshot',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'context_snapshot' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
