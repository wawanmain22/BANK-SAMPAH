<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Tracks product stock movements: inbound from processing, outbound to sales
 * and redemptions. Every change goes through this service to keep an audit
 * trail in `product_movements`.
 */
class ProductInventoryService
{
    public function add(
        Product $product,
        float $quantity,
        string $reason,
        ?Model $sourceRef = null,
        ?User $createdBy = null,
        ?string $notes = null,
    ): ProductMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
        }

        return DB::transaction(function () use ($product, $quantity, $reason, $sourceRef, $createdBy, $notes) {
            $product->refresh();
            $product->stock = (float) $product->stock + $quantity;
            $product->save();

            return ProductMovement::create([
                'product_id' => $product->id,
                'direction' => 'in',
                'reason' => $reason,
                'quantity' => $quantity,
                'stock_after' => $product->stock,
                'source_ref_type' => $sourceRef?->getMorphClass(),
                'source_ref_id' => $sourceRef?->getKey(),
                'notes' => $notes,
                'created_by' => $createdBy?->id,
            ]);
        });
    }

    public function remove(
        Product $product,
        float $quantity,
        string $reason,
        ?Model $sourceRef = null,
        ?User $createdBy = null,
        ?string $notes = null,
    ): ProductMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
        }

        return DB::transaction(function () use ($product, $quantity, $reason, $sourceRef, $createdBy, $notes) {
            $product->refresh();

            if ((float) $product->stock < $quantity) {
                $available = number_format((float) $product->stock, 3, ',', '.');

                throw new InvalidArgumentException(
                    "Stok produk {$product->name} tidak cukup (tersedia {$available} {$product->unit})."
                );
            }

            $product->stock = (float) $product->stock - $quantity;
            $product->save();

            return ProductMovement::create([
                'product_id' => $product->id,
                'direction' => 'out',
                'reason' => $reason,
                'quantity' => $quantity,
                'stock_after' => $product->stock,
                'source_ref_type' => $sourceRef?->getMorphClass(),
                'source_ref_id' => $sourceRef?->getKey(),
                'notes' => $notes,
                'created_by' => $createdBy?->id,
            ]);
        });
    }
}
