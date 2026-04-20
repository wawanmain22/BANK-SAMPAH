<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\WasteCategory;
use App\Models\WasteItem;
use Illuminate\Validation\Rule;

trait WasteItemValidationRules
{
    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function wasteItemRules(?int $itemId = null): array
    {
        return [
            'waste_category_id' => ['required', 'integer', Rule::exists(WasteCategory::class, 'id')],
            'code' => [
                'required',
                'string',
                'max:16',
                $itemId === null
                    ? Rule::unique(WasteItem::class, 'code')
                    : Rule::unique(WasteItem::class, 'code')->ignore($itemId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:16'],
            'price_per_unit' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
