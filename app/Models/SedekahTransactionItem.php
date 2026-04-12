<?php

namespace App\Models;

use Database\Factories\SedekahTransactionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sedekah_transaction_id',
    'waste_category_id',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }
}
