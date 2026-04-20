<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['waste_item_id', 'source', 'stock'])]
class Inventory extends Model
{
    public const SOURCE_NABUNG = 'nabung';

    public const SOURCE_SEDEKAH = 'sedekah';

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:3',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WasteItem::class, 'waste_item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'waste_item_id', 'waste_item_id')
            ->where('source', $this->source);
    }

    public function scopeNabung(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_NABUNG);
    }

    public function scopeSedekah(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_SEDEKAH);
    }
}
