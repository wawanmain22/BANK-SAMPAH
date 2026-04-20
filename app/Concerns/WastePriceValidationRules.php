<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\WasteItem;
use Illuminate\Validation\Rule;

trait WastePriceValidationRules
{
    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function wastePriceRules(): array
    {
        return [
            'waste_item_id' => ['required', 'integer', Rule::exists(WasteItem::class, 'id')],
            'price_per_unit' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'effective_from' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
