<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['waste_category_id', 'stock'])]
class Inventory extends Model
{
    protected function casts(): array
    {
        return [
            'stock' => 'decimal:3',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'waste_category_id', 'waste_category_id');
    }
}
