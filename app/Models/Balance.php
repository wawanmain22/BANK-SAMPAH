<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'saldo_tertahan', 'saldo_tersedia', 'points'])]
class Balance extends Model
{
    protected function casts(): array
    {
        return [
            'saldo_tertahan' => 'decimal:2',
            'saldo_tersedia' => 'decimal:2',
            'points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
