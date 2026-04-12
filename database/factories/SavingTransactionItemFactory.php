<?php

namespace Database\Factories;

use App\Models\SavingTransaction;
use App\Models\SavingTransactionItem;
use App\Models\WasteCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingTransactionItem>
 */
class SavingTransactionItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 0.5, 10);
        $price = fake()->randomFloat(2, 500, 15000);

        return [
            'saving_transaction_id' => SavingTransaction::factory(),
            'waste_category_id' => WasteCategory::factory(),
            'waste_price_id' => null,
            'category_name_snapshot' => fake()->words(2, true),
            'unit_snapshot' => 'kg',
            'price_per_unit_snapshot' => $price,
            'quantity' => $quantity,
            'subtotal' => round($quantity * $price, 2),
        ];
    }
}
