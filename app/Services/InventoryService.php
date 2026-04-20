<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Models\WasteItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Dual-source inventory ledger.
 *
 * Stock is partitioned by `source` (nabung | sedekah). Sampah nabung is sold to
 * mitra; sampah sedekah is processed into products. Mixing is not allowed —
 * callers must pass the correct source explicitly.
 */
class InventoryService
{
    public const SOURCE_NABUNG = Inventory::SOURCE_NABUNG;

    public const SOURCE_SEDEKAH = Inventory::SOURCE_SEDEKAH;

    private const VALID_SOURCES = [self::SOURCE_NABUNG, self::SOURCE_SEDEKAH];

    public function add(
        WasteItem $item,
        string $source,
        float $quantity,
        string $reason,
        ?Model $sourceRef = null,
        ?User $createdBy = null,
        ?string $notes = null,
    ): InventoryMovement {
        $this->assertValidSource($source);

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
        }

        return DB::transaction(function () use ($item, $source, $quantity, $reason, $sourceRef, $createdBy, $notes) {
            $inventory = Inventory::firstOrCreate(
                ['waste_item_id' => $item->id, 'source' => $source],
                ['stock' => 0],
            );
            $inventory->stock = (float) $inventory->stock + $quantity;
            $inventory->save();

            return InventoryMovement::create([
                'waste_item_id' => $item->id,
                'source' => $source,
                'direction' => 'in',
                'reason' => $reason,
                'quantity' => $quantity,
                'stock_after' => $inventory->stock,
                'source_ref_type' => $sourceRef?->getMorphClass(),
                'source_ref_id' => $sourceRef?->getKey(),
                'notes' => $notes,
                'created_by' => $createdBy?->id,
            ]);
        });
    }

    public function remove(
        WasteItem $item,
        string $source,
        float $quantity,
        string $reason,
        ?Model $sourceRef = null,
        ?User $createdBy = null,
        ?string $notes = null,
    ): InventoryMovement {
        $this->assertValidSource($source);

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Kuantitas harus lebih dari 0.');
        }

        return DB::transaction(function () use ($item, $source, $quantity, $reason, $sourceRef, $createdBy, $notes) {
            $inventory = Inventory::firstOrCreate(
                ['waste_item_id' => $item->id, 'source' => $source],
                ['stock' => 0],
            );

            if ((float) $inventory->stock < $quantity) {
                $available = number_format((float) $inventory->stock, 3, ',', '.');
                $label = $this->sourceLabel($source);

                throw new InvalidArgumentException(
                    "Stok {$item->name} ({$label}) tidak cukup (tersedia {$available} {$item->unit})."
                );
            }

            $inventory->stock = (float) $inventory->stock - $quantity;
            $inventory->save();

            return InventoryMovement::create([
                'waste_item_id' => $item->id,
                'source' => $source,
                'direction' => 'out',
                'reason' => $reason,
                'quantity' => $quantity,
                'stock_after' => $inventory->stock,
                'source_ref_type' => $sourceRef?->getMorphClass(),
                'source_ref_id' => $sourceRef?->getKey(),
                'notes' => $notes,
                'created_by' => $createdBy?->id,
            ]);
        });
    }

    public function stockFor(WasteItem $item, string $source): float
    {
        $this->assertValidSource($source);

        return (float) (Inventory::query()
            ->where('waste_item_id', $item->id)
            ->where('source', $source)
            ->value('stock') ?? 0);
    }

    private function assertValidSource(string $source): void
    {
        if (! in_array($source, self::VALID_SOURCES, true)) {
            throw new InvalidArgumentException(
                "Sumber inventory tidak valid: '{$source}'. Harus 'nabung' atau 'sedekah'."
            );
        }
    }

    private function sourceLabel(string $source): string
    {
        return $source === self::SOURCE_NABUNG ? 'sampah nabung' : 'sampah sedekah';
    }
}
