<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PointRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'points_per_rupiah',
    'rupiah_per_point',
    'effective_from',
    'notes',
    'is_active',
    'created_by',
])]
class PointRule extends Model
{
    /** @use HasFactory<PointRuleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'points_per_rupiah' => 'decimal:6',
            'rupiah_per_point' => 'decimal:2',
            'effective_from' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PointHistory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function resolveActive(?\DateTimeInterface $at = null): ?self
    {
        $date = ($at ?? now())->format('Y-m-d');

        return self::query()
            ->active()
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }
}
