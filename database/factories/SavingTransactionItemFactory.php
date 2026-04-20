<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SavingTransaction;
use App\Models\SavingTransactionItem;
use App\Models\WasteItem;
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
            'waste_item_id' => WasteItem::factory(),
            'waste_price_id' => null,
            'item_code_snapshot' => strtoupper(fake()->bothify('??#')),
            'item_name_snapshot' => ucfirst(fake()->words(2, true)),
            'category_name_snapshot' => ucfirst(fake()->word()),
            'unit_snapshot' => 'kg',
            'price_per_unit_snapshot' => $price,
            'quantity' => $quantity,
            'subtotal' => round($quantity * $price, 2),
        ];
    }
}
