<?php

namespace App\Models;

use Database\Factories\WithdrawalRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'amount',
    'method',
    'bank_name',
    'account_number',
    'account_name',
    'notes',
    'processed_by',
    'processed_at',
])]
class WithdrawalRequest extends Model
{
    /** @use HasFactory<WithdrawalRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
