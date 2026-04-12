<?php

namespace App\Models;

use Database\Factories\SavingTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'total_weight', 'total_value', 'points_awarded', 'notes', 'created_by', 'transacted_at'])]
class SavingTransaction extends Model
{
    /** @use HasFactory<SavingTransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:3',
            'total_value' => 'decimal:2',
            'points_awarded' => 'integer',
            'transacted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SavingTransactionItem::class);
    }
}
