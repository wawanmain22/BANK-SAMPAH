<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Partner;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\User;
use App\Models\WasteItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a sale of waste to a mitra (partner).
 *
 * Only consumes from the `nabung` inventory pool — sampah sedekah is reserved
 * for processing and cannot be sold to mitra.
 */
class SalesTransactionService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<int, array{waste_item_id: int, quantity: float|string, price_per_unit: float|string}>  $items
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
                $wasteItem = WasteItem::with('category')->findOrFail($item['waste_item_id']);
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
                    'item' => $wasteItem,
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
                $wasteItem = $row['item'];

                SalesTransactionItem::create([
                    'sales_transaction_id' => $transaction->id,
                    'waste_item_id' => $wasteItem->id,
                    'item_code_snapshot' => $wasteItem->code,
                    'item_name_snapshot' => $wasteItem->name,
                    'category_name_snapshot' => $wasteItem->category->name,
                    'unit_snapshot' => $wasteItem->unit,
                    'price_per_unit' => $row['price'],
                    'quantity' => $row['quantity'],
                    'subtotal' => $row['subtotal'],
                ]);

                // Throws if nabung stock insufficient — rolls back entire transaction.
                $this->inventoryService->remove(
                    item: $wasteItem,
                    source: InventoryService::SOURCE_NABUNG,
                    quantity: $row['quantity'],
                    reason: 'sale',
                    sourceRef: $transaction,
                    createdBy: $createdBy,
                );
            }

            return $transaction->load('items', 'partner');
        });
    }
}
