<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PointRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointRule>
 */
class PointRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'points_per_rupiah' => 0.001,
            'rupiah_per_point' => 1000,
            'effective_from' => now()->toDateString(),
            'notes' => null,
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
