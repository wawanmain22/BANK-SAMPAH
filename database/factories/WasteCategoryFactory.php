<?php

namespace Database\Factories;

use App\Models\WasteCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WasteCategory>
 */
class WasteCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => fake()->optional()->sentence(),
            'unit' => 'kg',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
