<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Models\WasteCategory;
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
        $category = WasteCategory::factory()->create();

        $movement = app(InventoryService::class)->add(
            $category, 5.5, 'adjustment', createdBy: $admin, notes: 'Initial stock'
        );

        $this->assertInstanceOf(InventoryMovement::class, $movement);
        $this->assertSame('in', $movement->direction);
        $this->assertSame('5.500', (string) $movement->quantity);
        $this->assertSame('5.500', (string) $movement->stock_after);

        $this->assertSame('5.500', (string) Inventory::firstWhere('waste_category_id', $category->id)->stock);
    }

    public function test_remove_decrements_stock(): void
    {
        $category = WasteCategory::factory()->create();
        $service = app(InventoryService::class);

        $service->add($category, 10, 'adjustment');
        $service->remove($category, 3, 'sale');

        $this->assertSame('7.000', (string) Inventory::firstWhere('waste_category_id', $category->id)->stock);
    }

    public function test_cannot_remove_more_than_stock(): void
    {
        $category = WasteCategory::factory()->create();
        $service = app(InventoryService::class);

        $service->add($category, 2, 'adjustment');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak cukup');

        $service->remove($category, 5, 'sale');
    }

    public function test_saving_transaction_auto_creates_inventory_movement(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        $admin = User::factory()->admin()->create();
        $category = WasteCategory::factory()->create();
        WastePrice::factory()->create([
            'waste_category_id' => $category->id,
            'price_per_unit' => 3000,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        app(SavingTransactionService::class)->create(
            $nasabah,
            [['waste_category_id' => $category->id, 'quantity' => 4]],
            createdBy: $admin,
        );

        $this->assertSame('4.000', (string) Inventory::firstWhere('waste_category_id', $category->id)->stock);

        $this->assertDatabaseHas('inventory_movements', [
            'waste_category_id' => $category->id,
            'direction' => 'in',
            'reason' => 'nabung',
            'quantity' => '4.000',
            'stock_after' => '4.000',
            'source_type' => SavingTransaction::class,
        ]);
    }
}
