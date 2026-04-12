<?php

namespace App\Models;

use Database\Factories\SalesTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['partner_id', 'total_weight', 'total_value', 'notes', 'created_by', 'transacted_at'])]
class SalesTransaction extends Model
{
    /** @use HasFactory<SalesTransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:3',
            'total_value' => 'decimal:2',
            'transacted_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesTransactionItem::class);
    }
}
