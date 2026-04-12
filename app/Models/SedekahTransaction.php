<?php

namespace App\Models;

use Database\Factories\SedekahTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'donor_name', 'total_weight', 'notes', 'created_by', 'transacted_at'])]
class SedekahTransaction extends Model
{
    /** @use HasFactory<SedekahTransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:3',
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
        return $this->hasMany(SedekahTransactionItem::class);
    }
}
