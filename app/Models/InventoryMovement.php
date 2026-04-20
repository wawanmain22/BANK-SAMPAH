<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'waste_item_id',
    'source',
    'direction',
    'reason',
    'quantity',
    'stock_after',
    'source_ref_type',
    'source_ref_id',
    'notes',
    'created_by',
])]
class InventoryMovement extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'stock_after' => 'decimal:3',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WasteItem::class, 'waste_item_id');
    }

    public function sourceRef(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeNabung(Builder $query): Builder
    {
        return $query->where('source', Inventory::SOURCE_NABUNG);
    }

    public function scopeSedekah(Builder $query): Builder
    {
        return $query->where('source', Inventory::SOURCE_SEDEKAH);
    }
}
