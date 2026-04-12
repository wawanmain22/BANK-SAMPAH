<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'waste_category_id',
    'direction',
    'reason',
    'quantity',
    'stock_after',
    'source_type',
    'source_id',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
