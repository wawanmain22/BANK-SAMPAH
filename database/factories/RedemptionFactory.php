<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Redemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redemption>
 */
class RedemptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->nasabah(),
            'product_id' => Product::factory(),
            'product_name_snapshot' => fake()->words(2, true),
            'unit_snapshot' => 'pcs',
            'quantity' => 1,
            'points_used' => fake()->numberBetween(10, 500),
            'notes' => null,
            'processed_by' => null,
            'redeemed_at' => now(),
        ];
    }
}
