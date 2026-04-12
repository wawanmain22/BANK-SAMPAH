<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'type' => fake()->randomElement(['pengepul', 'pabrik', 'lainnya']),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
