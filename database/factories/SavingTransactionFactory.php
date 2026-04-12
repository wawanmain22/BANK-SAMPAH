<?php

namespace Database\Factories;

use App\Models\SavingTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingTransaction>
 */
class SavingTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->nasabah(),
            'total_weight' => fake()->randomFloat(3, 0.5, 50),
            'total_value' => fake()->randomFloat(2, 1000, 500000),
            'points_awarded' => 0,
            'notes' => null,
            'created_by' => null,
            'transacted_at' => now(),
        ];
    }
}
