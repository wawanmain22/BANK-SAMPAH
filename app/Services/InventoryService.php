<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Models\WasteCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Record an inbound movement (sampah masuk) and increment stock.
     */
    public function add(
        WasteCategory $category,
        float $quantity,
        string $reason,
        ?Model $source = null,
        ?User $createdBy = null,
        ?string $notes = null,
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
        }

        return DB::transaction(function () use ($category, $quantity, $reason, $source, $createdBy, $notes) {
            $inventory = Inventory::firstOrCreate(['waste_category_id' => $category->id]);
            $inventory->stock = (float) $inventory->stock + $quantity;
            $inventory->save();

            return InventoryMovement::create([
                'waste_category_id' => $category->id,
                'direction' => 'in',
                'reason' => $reason,
                'quantity' => $quantity,
                'stock_after' => $inventory->stock,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'notes' => $notes,
                'created_by' => $createdBy?->id,
            ]);
        });
    }

    /**
     * Record an outbound movement (sampah keluar) and decrement stock.
     */
    public function remove(
        WasteCategory $category,
        float $quantity,
        string $reason,
        ?Model $source = null,
        ?User $createdBy = null,
        ?string $notes = null,
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
        }

        return DB::transaction(function () use ($category, $quantity, $reason, $source, $createdBy, $notes) {
            $inventory = Inventory::firstOrCreate(['waste_category_id' => $category->id]);

            if ((float) $inventory->stock < $quantity) {
                throw new InvalidArgumentException(
                    "Stok {$category->name} tidak cukup (tersedia ".number_format((float) $inventory->stock, 3, ',', '.').' '.$category->unit.').'
                );
            }

            $inventory->stock = (float) $inventory->stock - $quantity;
            $inventory->save();

            return InventoryMovement::create([
                'waste_category_id' => $category->id,
                'direction' => 'out',
                'reason' => $reason,
                'quantity' => $quantity,
                'stock_after' => $inventory->stock,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'notes' => $notes,
                'created_by' => $createdBy?->id,
            ]);
        });
    }
}
