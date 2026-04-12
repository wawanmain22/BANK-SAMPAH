<?php

namespace App\Services;

use App\Models\SedekahTransaction;
use App\Models\SedekahTransactionItem;
use App\Models\User;
use App\Models\WasteCategory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a sedekah (donated waste) transaction.
 *
 * Unlike saving, sedekah produces NO saldo and NO points — the donated waste
 * simply flows into inventory for later processing or sale.
 */
class SedekahTransactionService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<int, array{waste_category_id: int, quantity: float|string}>  $items
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
                $category = WasteCategory::findOrFail($item['waste_category_id']);
                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
                }

                $prepared[] = [
                    'category' => $category,
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
                SedekahTransactionItem::create([
                    'sedekah_transaction_id' => $transaction->id,
                    'waste_category_id' => $row['category']->id,
                    'category_name_snapshot' => $row['category']->name,
                    'unit_snapshot' => $row['category']->unit,
                    'quantity' => $row['quantity'],
                ]);

                $this->inventoryService->add(
                    category: $row['category'],
                    quantity: $row['quantity'],
                    reason: 'sedekah',
                    source: $transaction,
                    createdBy: $createdBy,
                );
            }

            return $transaction->load('items');
        });
    }
}
