<?php

namespace App\Models;

use Database\Factories\WastePriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['waste_category_id', 'price_per_unit', 'effective_from', 'notes', 'created_by'])]
class WastePrice extends Model
{
    /** @use HasFactory<WastePriceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_per_unit' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
