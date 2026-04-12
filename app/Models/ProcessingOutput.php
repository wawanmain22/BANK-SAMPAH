<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'processing_transaction_id',
    'product_id',
    'product_name_snapshot',
    'unit_snapshot',
    'quantity',
])]
class ProcessingOutput extends Model
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
