<?php

namespace App\Concerns;

use App\Models\WasteCategory;
use Illuminate\Validation\Rule;

trait WasteCategoryValidationRules
{
    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function wasteCategoryRules(?int $categoryId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                $categoryId === null
                    ? Rule::unique(WasteCategory::class, 'name')
                    : Rule::unique(WasteCategory::class, 'name')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit' => ['required', 'string', 'max:16'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
