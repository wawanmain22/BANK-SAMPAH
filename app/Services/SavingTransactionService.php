<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\BalanceHistory;
use App\Models\PointHistory;
use App\Models\SavingTransaction;
use App\Models\SavingTransactionItem;
use App\Models\User;
use App\Models\WasteCategory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates a saving transaction atomically.
 *
 * Snapshots each item's price so historical transactions stay intact even if
 * category prices change. Updates `saldo_tertahan` and awards points to members.
 */
class SavingTransactionService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<int, array{waste_category_id: int, quantity: float|string}>  $items
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
                $category = WasteCategory::with('currentPrice')->findOrFail($item['waste_category_id']);
                $price = $category->currentPrice;

                if (! $price) {
                    throw new InvalidArgumentException(
                        "Kategori '{$category->name}' belum memiliki harga aktif."
                    );
                }

                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
                }

                $subtotal = round($quantity * (float) $price->price_per_unit, 2);

                $preparedItems[] = [
                    'waste_category_id' => $category->id,
                    'waste_price_id' => $price->id,
                    'category_name_snapshot' => $category->name,
                    'unit_snapshot' => $category->unit,
                    'price_per_unit_snapshot' => (float) $price->price_per_unit,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];

                $totalWeight += $quantity;
                $totalValue += $subtotal;
            }

            $points = 0;

            if ($nasabah->is_member) {
                $points = (int) floor($totalValue * (float) config('banksampah.points_per_rupiah'));
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

            foreach ($preparedItems as $itemIndex => $item) {
                SavingTransactionItem::create([
                    'saving_transaction_id' => $transaction->id,
                    ...$item,
                ]);

                $this->inventoryService->add(
                    category: WasteCategory::find($item['waste_category_id']),
                    quantity: (float) $item['quantity'],
                    reason: 'nabung',
                    source: $transaction,
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
                    'type' => 'earn',
                    'points' => $points,
                    'balance_after' => $balance->points,
                    'source_type' => SavingTransaction::class,
                    'source_id' => $transaction->id,
                    'description' => 'Poin dari transaksi nabung #'.$transaction->id,
                    'created_by' => $createdBy?->id,
                ]);
            }

            return $transaction->load('items.category');
        });
    }
}
