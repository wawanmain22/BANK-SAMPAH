<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'processing_transaction_id',
    'waste_category_id',
    'category_name_snapshot',
    'unit_snapshot',
    'quantity',
])]
class ProcessingInput extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(ProcessingTransaction::class, 'processing_transaction_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }
}
