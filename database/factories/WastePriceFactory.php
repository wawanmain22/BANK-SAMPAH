<?php

namespace Database\Factories;

use App\Models\WasteCategory;
use App\Models\WastePrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WastePrice>
 */
class WastePriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'waste_category_id' => WasteCategory::factory(),
            'price_per_unit' => fake()->randomFloat(2, 500, 20000),
            'effective_from' => now()->toDateString(),
            'notes' => null,
            'created_by' => null,
        ];
    }
}
