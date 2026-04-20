<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Balance;
use App\Models\PointHistory;
use App\Models\Product;
use App\Models\Redemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Processes a member redemption: deducts points, decrements product stock
 * via ProductInventoryService (audit trail), logs a point_history row.
 */
class RedemptionService
{
    public function __construct(
        private ProductInventoryService $productInventoryService,
    ) {}

    public function create(
        User $nasabah,
        Product $product,
        float $quantity,
        int $pointsUsed,
        ?User $processedBy = null,
        ?string $notes = null,
    ): Redemption {
        if (! $nasabah->isNasabah()) {
            throw new InvalidArgumentException('User must be a nasabah.');
        }

        if (! $nasabah->is_member) {
            throw new InvalidArgumentException('Hanya member yang bisa menukar poin.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
        }

        if ($pointsUsed <= 0) {
            throw new InvalidArgumentException('Poin yang ditukar harus lebih dari 0.');
        }

        return DB::transaction(function () use ($nasabah, $product, $quantity, $pointsUsed, $processedBy, $notes) {
            $balance = Balance::firstOrCreate(['user_id' => $nasabah->id]);

            if ((int) $balance->points < $pointsUsed) {
                throw new InvalidArgumentException('Poin nasabah tidak cukup.');
            }

            $balance->points = (int) $balance->points - $pointsUsed;
            $balance->save();

            $redemption = Redemption::create([
                'user_id' => $nasabah->id,
                'product_id' => $product->id,
                'product_name_snapshot' => $product->name,
                'unit_snapshot' => $product->unit,
                'quantity' => $quantity,
                'points_used' => $pointsUsed,
                'notes' => $notes,
                'processed_by' => $processedBy?->id,
                'redeemed_at' => now(),
            ]);

            // Throws if stock insufficient — rolls back entire transaction.
            $this->productInventoryService->remove(
                product: $product,
                quantity: $quantity,
                reason: 'redemption',
                sourceRef: $redemption,
                createdBy: $processedBy,
            );

            PointHistory::create([
                'user_id' => $nasabah->id,
                'type' => 'redeem',
                'points' => -$pointsUsed,
                'balance_after' => $balance->points,
                'source_type' => Redemption::class,
                'source_id' => $redemption->id,
                'description' => "Tukar {$quantity} {$product->unit} {$product->name}",
                'created_by' => $processedBy?->id,
            ]);

            return $redemption;
        });
    }
}
