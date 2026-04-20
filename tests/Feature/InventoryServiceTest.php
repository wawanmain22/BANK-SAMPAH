<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Models\WasteItem;
use App\Models\WastePrice;
use App\Services\InventoryService;
use App\Services\SavingTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_increments_stock_and_records_movement(): void
    {
        $admin = User::factory()->admin()->create();
        $item = WasteItem::factory()->create();

        $movement = app(InventoryService::class)->add(
            item: $item,
            source: InventoryService::SOURCE_NABUNG,
            quantity: 5.5,
            reason: 'adjustment',
            createdBy: $admin,
            notes: 'Initial stock',
        );

        $this->assertInstanceOf(InventoryMovement::class, $movement);
        $this->assertSame('in', $movement->direction);
        $this->assertSame('nabung', $movement->source);
        $this->assertSame('5.500', (string) $movement->quantity);
        $this->assertSame('5.500', (string) $movement->stock_after);

        $inv = Inventory::where('waste_item_id', $item->id)
            ->where('source', InventoryService::SOURCE_NABUNG)->first();
        $this->assertSame('5.500', (string) $inv->stock);
    }

    public function test_nabung_and_sedekah_pools_are_independent(): void
    {
        $item = WasteItem::factory()->create();
        $service = app(InventoryService::class);

        $service->add($item, InventoryService::SOURCE_NABUNG, 10, 'adjustment');
        $service->add($item, InventoryService::SOURCE_SEDEKAH, 3, 'adjustment');

        $this->assertSame(10.0, $service->stockFor($item, InventoryService::SOURCE_NABUNG));
        $this->assertSame(3.0, $service->stockFor($item, InventoryService::SOURCE_SEDEKAH));
    }

    public function test_remove_decrements_stock(): void
    {
        $item = WasteItem::factory()->create();
        $service = app(InventoryService::class);

        $service->add($item, InventoryService::SOURCE_NABUNG, 10, 'adjustment');
        $service->remove($item, InventoryService::SOURCE_NABUNG, 3, 'sale');

        $this->assertSame(7.0, $service->stockFor($item, InventoryService::SOURCE_NABUNG));
    }

    public function test_cannot_remove_more_than_stock(): void
    {
        $item = WasteItem::factory()->create();
        $service = app(InventoryService::class);

        $service->add($item, InventoryService::SOURCE_NABUNG, 2, 'adjustment');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak cukup');

        $service->remove($item, InventoryService::SOURCE_NABUNG, 5, 'sale');
    }

    public function test_remove_from_empty_pool_fails_even_if_other_pool_has_stock(): void
    {
        $item = WasteItem::factory()->create();
        $service = app(InventoryService::class);

        $service->add($item, InventoryService::SOURCE_SEDEKAH, 10, 'adjustment');

        $this->expectException(InvalidArgumentException::class);

        $service->remove($item, InventoryService::SOURCE_NABUNG, 1, 'sale');
    }

    public function test_invalid_source_rejected(): void
    {
        $item = WasteItem::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak valid');

        app(InventoryService::class)->add($item, 'bogus', 1, 'adjustment');
    }

    public function test_saving_transaction_auto_feeds_nabung_pool(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        $admin = User::factory()->admin()->create();
        $item = WasteItem::factory()->create(['price_per_unit' => 3000]);
        WastePrice::factory()->create([
            'waste_item_id' => $item->id,
            'price_per_unit' => 3000,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        app(SavingTransactionService::class)->create(
            $nasabah,
            [['waste_item_id' => $item->id, 'quantity' => 4]],
            createdBy: $admin,
        );

        $nabung = Inventory::where('waste_item_id', $item->id)
            ->where('source', InventoryService::SOURCE_NABUNG)->first();
        $this->assertSame('4.000', (string) $nabung->stock);

        $this->assertDatabaseHas('inventory_movements', [
            'waste_item_id' => $item->id,
            'source' => 'nabung',
            'direction' => 'in',
            'reason' => 'nabung',
            'quantity' => '4.000',
            'stock_after' => '4.000',
            'source_ref_type' => SavingTransaction::class,
        ]);
    }
}
