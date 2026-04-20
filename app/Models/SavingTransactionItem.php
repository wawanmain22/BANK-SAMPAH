<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SavingTransactionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'saving_transaction_id',
    'waste_item_id',
    'waste_price_id',
    'item_code_snapshot',
    'item_name_snapshot',
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(WasteItem::class, 'waste_item_id');
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(WastePrice::class, 'waste_price_id');
    }
}
