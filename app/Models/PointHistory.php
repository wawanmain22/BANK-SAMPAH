<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id',
    'point_rule_id',
    'type',
    'points',
    'balance_after',
    'rate_snapshot',
    'source_type',
    'source_id',
    'description',
    'created_by',
])]
class PointHistory extends Model
{
    protected function casts(): array
    {
        return [
            'rate_snapshot' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PointRule::class, 'point_rule_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
