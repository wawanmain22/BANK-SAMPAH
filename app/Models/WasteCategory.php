<?php

namespace App\Models;

use Database\Factories\WasteCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'slug', 'description', 'unit', 'is_active'])]
class WasteCategory extends Model
{
    /** @use HasFactory<WasteCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(WastePrice::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
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
