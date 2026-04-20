<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SedekahTransactionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sedekah_transaction_id',
    'waste_item_id',
    'item_code_snapshot',
    'item_name_snapshot',
    'category_name_snapshot',
    'unit_snapshot',
    'quantity',
])]
class SedekahTransactionItem extends Model
{
    /** @use HasFactory<SedekahTransactionItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SedekahTransaction::class, 'sedekah_transaction_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WasteItem::class, 'waste_item_id');
    }
}
