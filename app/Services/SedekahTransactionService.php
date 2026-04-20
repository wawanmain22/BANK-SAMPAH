<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SedekahTransaction;
use App\Models\SedekahTransactionItem;
use App\Models\User;
use App\Models\WasteItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a sedekah (donated waste) transaction.
 *
 * Produces NO saldo and NO points — the donated waste flows into the `sedekah`
 * inventory pool, later consumed by waste processing into products.
 */
class SedekahTransactionService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<int, array{waste_item_id: int, quantity: float|string}>  $items
     */
    public function create(
        array $items,
        ?User $donor = null,
        ?string $donorName = null,
        ?string $notes = null,
        ?User $createdBy = null,
    ): SedekahTransaction {
        if (empty($items)) {
            throw new InvalidArgumentException('Minimal satu item sampah.');
        }

        if ($donor && ! $donor->isNasabah()) {
            throw new InvalidArgumentException('Donor harus nasabah terdaftar atau kosong.');
        }

        return DB::transaction(function () use ($items, $donor, $donorName, $notes, $createdBy) {
            $prepared = [];
            $totalWeight = 0.0;

            foreach ($items as $item) {
                $wasteItem = WasteItem::with('category')->findOrFail($item['waste_item_id']);
                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
                }

                $prepared[] = [
                    'item' => $wasteItem,
                    'quantity' => $quantity,
                ];
                $totalWeight += $quantity;
            }

            $transaction = SedekahTransaction::create([
                'user_id' => $donor?->id,
                'donor_name' => $donorName ?: $donor?->name,
                'total_weight' => $totalWeight,
                'notes' => $notes,
                'created_by' => $createdBy?->id,
                'transacted_at' => now(),
            ]);

            foreach ($prepared as $row) {
                $wasteItem = $row['item'];

                SedekahTransactionItem::create([
                    'sedekah_transaction_id' => $transaction->id,
                    'waste_item_id' => $wasteItem->id,
                    'item_code_snapshot' => $wasteItem->code,
                    'item_name_snapshot' => $wasteItem->name,
                    'category_name_snapshot' => $wasteItem->category->name,
                    'unit_snapshot' => $wasteItem->unit,
                    'quantity' => $row['quantity'],
                ]);

                $this->inventoryService->add(
                    item: $wasteItem,
                    source: InventoryService::SOURCE_SEDEKAH,
                    quantity: $row['quantity'],
                    reason: 'sedekah',
                    sourceRef: $transaction,
                    createdBy: $createdBy,
                );
            }

            return $transaction->load('items');
        });
    }
}
