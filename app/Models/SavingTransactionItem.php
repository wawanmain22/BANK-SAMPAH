<?php

namespace App\Models;

use Database\Factories\SavingTransactionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'saving_transaction_id',
    'waste_category_id',
    'waste_price_id',
    'category_name_snapshot',
    'unit_snapshot',
    'price_per_unit_snapshot',
    'quantity',
    'subtotal',
])]
class SavingTransactionItem extends Model
{
    /** @use HasFactory<SavingTransactionItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_per_unit_snapshot' => 'decimal:2',
            'quantity' => 'decimal:3',
            'subtotal' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SavingTransaction::class, 'saving_transaction_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(WastePrice::class, 'waste_price_id');
    }
}
