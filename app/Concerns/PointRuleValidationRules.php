<?php

declare(strict_types=1);

namespace App\Concerns;

trait PointRuleValidationRules
{
    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function pointRuleRules(): array
    {
        return [
            'points_per_rupiah' => ['required', 'numeric', 'min:0', 'max:1000'],
            'rupiah_per_point' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'effective_from' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
