<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'point_rule_id',
    'points_used',
    'rate_snapshot',
    'cash_amount',
    'notes',
    'processed_by',
    'cashed_out_at',
])]
class PointCashOut extends Model
{
    protected function casts(): array
    {
        return [
            'points_used' => 'integer',
            'rate_snapshot' => 'decimal:2',
            'cash_amount' => 'decimal:2',
            'cashed_out_at' => 'datetime',
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

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
