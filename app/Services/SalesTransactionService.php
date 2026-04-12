<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\User;
use App\Models\WasteCategory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a sale of waste to a mitra (partner).
 *
 * Decrements inventory per item and stores the agreed sell price, which may
 * differ from the current category price (mitra negotiate per delivery).
 */
class SalesTransactionService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<int, array{waste_category_id: int, quantity: float|string, price_per_unit: float|string}>  $items
     */
    public function create(
        Partner $partner,
        array $items,
        ?string $notes = null,
        ?User $createdBy = null,
    ): SalesTransaction {
        if (empty($items)) {
            throw new InvalidArgumentException('Minimal satu item penjualan.');
        }

        return DB::transaction(function () use ($partner, $items, $notes, $createdBy) {
            $prepared = [];
            $totalWeight = 0.0;
            $totalValue = 0.0;

            foreach ($items as $item) {
                $category = WasteCategory::findOrFail($item['waste_category_id']);
                $quantity = (float) $item['quantity'];
                $price = (float) $item['price_per_unit'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
                }

                if ($price < 0) {
                    throw new InvalidArgumentException('Harga tidak valid.');
                }

                $subtotal = round($quantity * $price, 2);

                $prepared[] = [
                    'category' => $category,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];

                $totalWeight += $quantity;
                $totalValue += $subtotal;
            }

            $transaction = SalesTransaction::create([
                'partner_id' => $partner->id,
                'total_weight' => $totalWeight,
                'total_value' => $totalValue,
                'notes' => $notes,
                'created_by' => $createdBy?->id,
                'transacted_at' => now(),
            ]);

            foreach ($prepared as $row) {
                SalesTransactionItem::create([
                    'sales_transaction_id' => $transaction->id,
                    'waste_category_id' => $row['category']->id,
                    'category_name_snapshot' => $row['category']->name,
                    'unit_snapshot' => $row['category']->unit,
                    'price_per_unit' => $row['price'],
                    'quantity' => $row['quantity'],
                    'subtotal' => $row['subtotal'],
                ]);

                // Will throw if stock insufficient - rolls back entire transaction
                $this->inventoryService->remove(
                    category: $row['category'],
                    quantity: $row['quantity'],
                    reason: 'sale',
                    source: $transaction,
                    createdBy: $createdBy,
                );
            }

            return $transaction->load('items', 'partner');
        });
    }
}
