<?php

namespace App\Models;

use Database\Factories\RedemptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'product_id',
    'product_name_snapshot',
    'unit_snapshot',
    'quantity',
    'points_used',
    'notes',
    'processed_by',
    'redeemed_at',
])]
class Redemption extends Model
{
    /** @use HasFactory<RedemptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'points_used' => 'integer',
            'redeemed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
