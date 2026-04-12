<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithdrawalRequest>
 */
class WithdrawalRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->nasabah(),
            'amount' => fake()->randomFloat(2, 5000, 500000),
            'method' => fake()->randomElement(['cash', 'transfer']),
            'bank_name' => null,
            'account_number' => null,
            'account_name' => null,
            'notes' => null,
            'processed_by' => null,
            'processed_at' => now(),
        ];
    }

    public function transfer(): static
    {
        return $this->state(fn () => [
            'method' => 'transfer',
            'bank_name' => fake()->randomElement(['BCA', 'BRI', 'Mandiri', 'BNI']),
            'account_number' => fake()->numerify('##########'),
            'account_name' => fake()->name(),
        ]);
    }
}
