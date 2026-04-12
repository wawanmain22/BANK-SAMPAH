<?php

namespace App\Concerns;

use App\Models\Product;
use Illuminate\Validation\Rule;

trait ProductValidationRules
{
    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function productRules(?int $productId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:128',
                $productId === null
                    ? Rule::unique(Product::class, 'name')
                    : Rule::unique(Product::class, 'name')->ignore($productId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'string', 'max:500'],
            'unit' => ['required', 'string', 'max:16'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
