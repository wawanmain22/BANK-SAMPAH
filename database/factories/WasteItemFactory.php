<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WasteCategory;
use App\Models\WasteItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WasteItem>
 */
class WasteItemFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));
        $code = strtoupper(fake()->unique()->bothify('??#'));

        return [
            'waste_category_id' => WasteCategory::factory(),
            'code' => $code,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'unit' => 'kg',
            'price_per_unit' => fake()->randomFloat(2, 500, 20000),
            'description' => null,
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
