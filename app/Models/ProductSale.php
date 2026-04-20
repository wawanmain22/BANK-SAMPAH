<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'buyer_user_id',
    'buyer_name',
    'buyer_phone',
    'payment_method',
    'payment_status',
    'total_quantity',
    'total_value',
    'notes',
    'created_by',
    'transacted_at',
])]
class ProductSale extends Model
{
    public const PAYMENT_METHODS = ['cash', 'transfer', 'qris'];

    public const PAYMENT_STATUSES = ['paid', 'pending'];

    protected function casts(): array
    {
        return [
            'total_quantity' => 'decimal:3',
            'total_value' => 'decimal:2',
            'transacted_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductSaleItem::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('payment_status', 'pending');
    }
}
