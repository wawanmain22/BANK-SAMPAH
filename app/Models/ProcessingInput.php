<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'processing_transaction_id',
    'waste_item_id',
    'item_code_snapshot',
    'item_name_snapshot',
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(WasteItem::class, 'waste_item_id');
    }
}
