<?php

namespace App\Models;

use Database\Factories\ProcessingTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['total_input_weight', 'notes', 'created_by', 'transacted_at'])]
class ProcessingTransaction extends Model
{
    /** @use HasFactory<ProcessingTransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_input_weight' => 'decimal:3',
            'transacted_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(ProcessingInput::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProcessingOutput::class);
    }
}
