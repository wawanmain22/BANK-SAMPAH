<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WasteItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'waste_category_id',
    'code',
    'name',
    'slug',
    'unit',
    'price_per_unit',
    'description',
    'is_active',
])]
class WasteItem extends Model
{
    /** @use HasFactory<WasteItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_per_unit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(WastePrice::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function currentPrice(): HasOne
    {
        return $this->hasOne(WastePrice::class)
            ->ofMany(
                ['effective_from' => 'max', 'id' => 'max'],
                fn ($query) => $query->where('effective_from', '<=', now()->toDateString()),
            );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
