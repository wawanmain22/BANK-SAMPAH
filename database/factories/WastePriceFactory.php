<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WasteItem;
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
            'waste_item_id' => WasteItem::factory(),
            'price_per_unit' => fake()->randomFloat(2, 500, 20000),
            'effective_from' => now()->toDateString(),
            'notes' => null,
            'created_by' => null,
        ];
    }
}
