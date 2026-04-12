<?php

namespace Database\Factories;

use App\Models\SedekahTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SedekahTransaction>
 */
class SedekahTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'donor_name' => fake()->name(),
            'total_weight' => fake()->randomFloat(3, 0.5, 30),
            'notes' => null,
            'created_by' => null,
            'transacted_at' => now(),
        ];
    }
}
