<?php

declare(strict_types=1);

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
            'code_prefix' => [
                'required',
                'string',
                'max:8',
                'alpha_num',
                $categoryId === null
                    ? Rule::unique(WasteCategory::class, 'code_prefix')
                    : Rule::unique(WasteCategory::class, 'code_prefix')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
