<?php

namespace App\Concerns;

trait PartnerValidationRules
{
    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function partnerRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128'],
            'type' => ['required', 'in:pengepul,pabrik,lainnya'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:128'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
