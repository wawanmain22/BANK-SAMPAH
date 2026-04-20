<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SalesTransactionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sales_transaction_id',
    'waste_item_id',
    'item_code_snapshot',
    'item_name_snapshot',
    'category_name_snapshot',
    'unit_snapshot',
    'price_per_unit',
    'quantity',
    'subtotal',
])]
class SalesTransactionItem extends Model
{
    /** @use HasFactory<SalesTransactionItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_per_unit' => 'decimal:2',
            'quantity' => 'decimal:3',
            'subtotal' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WasteItem::class, 'waste_item_id');
    }
}
