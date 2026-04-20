<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Balance;
use App\Models\BalanceHistory;
use App\Models\PointHistory;
use App\Models\PointRule;
use App\Models\SavingTransaction;
use App\Models\SavingTransactionItem;
use App\Models\User;
use App\Models\WasteItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates a saving transaction atomically.
 *
 * Snapshots each item's price so historical transactions stay intact even if
 * item prices change. Stock flows into the `nabung` inventory pool (later sold
 * to mitra). Points awarded using the active PointRule; rate is snapshot.
 */
class SavingTransactionService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<int, array{waste_item_id: int, quantity: float|string}>  $items
     */
    public function create(
        User $nasabah,
        array $items,
        ?string $notes = null,
        ?User $createdBy = null,
    ): SavingTransaction {
        if (! $nasabah->isNasabah()) {
            throw new InvalidArgumentException('User must be a nasabah.');
        }

        if (empty($items)) {
            throw new InvalidArgumentException('At least one item is required.');
        }

        return DB::transaction(function () use ($nasabah, $items, $notes, $createdBy) {
            $preparedItems = [];
            $totalWeight = 0.0;
            $totalValue = 0.0;

            foreach ($items as $item) {
                $wasteItem = WasteItem::with(['category', 'currentPrice'])->findOrFail($item['waste_item_id']);
                $price = $wasteItem->currentPrice;

                if (! $price) {
                    throw new InvalidArgumentException(
                        "Barang '{$wasteItem->name}' belum memiliki harga aktif."
                    );
                }

                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
                }

                $subtotal = round($quantity * (float) $price->price_per_unit, 2);

                $preparedItems[] = [
                    'waste_item' => $wasteItem,
                    'waste_price_id' => $price->id,
                    'item_code_snapshot' => $wasteItem->code,
                    'item_name_snapshot' => $wasteItem->name,
                    'category_name_snapshot' => $wasteItem->category->name,
                    'unit_snapshot' => $wasteItem->unit,
                    'price_per_unit_snapshot' => (float) $price->price_per_unit,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];

                $totalWeight += $quantity;
                $totalValue += $subtotal;
            }

            $rule = null;
            $rate = 0.0;
            $points = 0;

            if ($nasabah->is_member) {
                $rule = PointRule::resolveActive();
                $rate = (float) ($rule?->points_per_rupiah ?? 0);
                $points = (int) floor($totalValue * $rate);
            }

            $transaction = SavingTransaction::create([
                'user_id' => $nasabah->id,
                'total_weight' => $totalWeight,
                'total_value' => $totalValue,
                'points_awarded' => $points,
                'notes' => $notes,
                'created_by' => $createdBy?->id,
                'transacted_at' => now(),
            ]);

            foreach ($preparedItems as $row) {
                SavingTransactionItem::create([
                    'saving_transaction_id' => $transaction->id,
                    'waste_item_id' => $row['waste_item']->id,
                    'waste_price_id' => $row['waste_price_id'],
                    'item_code_snapshot' => $row['item_code_snapshot'],
                    'item_name_snapshot' => $row['item_name_snapshot'],
                    'category_name_snapshot' => $row['category_name_snapshot'],
                    'unit_snapshot' => $row['unit_snapshot'],
                    'price_per_unit_snapshot' => $row['price_per_unit_snapshot'],
                    'quantity' => $row['quantity'],
                    'subtotal' => $row['subtotal'],
                ]);

                $this->inventoryService->add(
                    item: $row['waste_item'],
                    source: InventoryService::SOURCE_NABUNG,
                    quantity: $row['quantity'],
                    reason: 'nabung',
                    sourceRef: $transaction,
                    createdBy: $createdBy,
                );
            }

            $balance = Balance::firstOrCreate(['user_id' => $nasabah->id]);
            $balance->saldo_tertahan = (float) $balance->saldo_tertahan + $totalValue;
            $balance->points = (int) $balance->points + $points;
            $balance->save();

            BalanceHistory::create([
                'user_id' => $nasabah->id,
                'bucket' => 'tertahan',
                'type' => 'nabung',
                'amount' => $totalValue,
                'balance_after' => $balance->saldo_tertahan,
                'source_type' => SavingTransaction::class,
                'source_id' => $transaction->id,
                'description' => 'Transaksi nabung #'.$transaction->id,
                'created_by' => $createdBy?->id,
            ]);

            if ($points > 0) {
                PointHistory::create([
                    'user_id' => $nasabah->id,
                    'point_rule_id' => $rule?->id,
                    'type' => 'earn',
                    'points' => $points,
                    'balance_after' => $balance->points,
                    'rate_snapshot' => $rate,
                    'source_type' => SavingTransaction::class,
                    'source_id' => $transaction->id,
                    'description' => 'Poin dari transaksi nabung #'.$transaction->id,
                    'created_by' => $createdBy?->id,
                ]);
            }

            return $transaction->load('items.item.category');
        });
    }
}
