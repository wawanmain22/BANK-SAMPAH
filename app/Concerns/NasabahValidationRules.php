<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Validation\Rule;

trait NasabahValidationRules
{
    /**
     * Validation rules for creating or updating a nasabah profile.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function nasabahRules(?int $userId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $userId === null
                    ? Rule::unique(User::class, 'email')
                    : Rule::unique(User::class, 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_member' => ['required', 'boolean'],
            'member_joined_at' => ['nullable', 'date', 'before_or_equal:today', 'required_if:is_member,true'],
        ];
    }
}
