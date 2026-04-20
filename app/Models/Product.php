<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'slug', 'description', 'image', 'unit', 'price', 'points_cost', 'stock', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'points_cost' => 'integer',
            'stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProductMovement::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(ProductSaleItem::class);
    }

    public function currentPrice(): HasOne
    {
        return $this->hasOne(ProductPrice::class)
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
