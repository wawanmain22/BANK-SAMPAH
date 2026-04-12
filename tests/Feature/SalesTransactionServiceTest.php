<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Partner;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Models\WasteCategory;
use App\Services\InventoryService;
use App\Services\SalesTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SalesTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_decrements_inventory_and_records(): void
    {
        $partner = Partner::factory()->create(['name' => 'CV Pengepul Jaya']);
        $admin = User::factory()->admin()->create();
        $category = WasteCategory::factory()->create();

        // Seed inventory
        app(InventoryService::class)->add($category, 20, 'adjustment');

        $transaction = app(SalesTransactionService::class)->create(
            $partner,
            [['waste_category_id' => $category->id, 'quantity' => 7.5, 'price_per_unit' => 4000]],
            notes: 'Kirim siang',
            createdBy: $admin,
        );

        $this->assertSame('30000.00', (string) $transaction->total_value);
        $this->assertSame('7.500', (string) $transaction->total_weight);
        $this->assertSame($partner->id, $transaction->partner_id);

        $this->assertSame('12.500', (string) Inventory::firstWhere('waste_category_id', $category->id)->stock);

        $this->assertDatabaseHas('inventory_movements', [
            'waste_category_id' => $category->id,
            'direction' => 'out',
            'reason' => 'sale',
            'quantity' => '7.500',
            'stock_after' => '12.500',
            'source_type' => SalesTransaction::class,
        ]);
    }

    public function test_cannot_sell_more_than_stock(): void
    {
        $partner = Partner::factory()->create();
        $category = WasteCategory::factory()->create();
        app(InventoryService::class)->add($category, 5, 'adjustment');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak cukup');

        app(SalesTransactionService::class)->create(
            $partner,
            [['waste_category_id' => $category->id, 'quantity' => 10, 'price_per_unit' => 1000]],
        );
    }

    public function test_rollback_if_one_item_exceeds_stock(): void
    {
        $partner = Partner::factory()->create();
        $catA = WasteCategory::factory()->create();
        $catB = WasteCategory::factory()->create();
        app(InventoryService::class)->add($catA, 10, 'adjustment');
        app(InventoryService::class)->add($catB, 2, 'adjustment');

        try {
            app(SalesTransactionService::class)->create(
                $partner,
                [
                    ['waste_category_id' => $catA->id, 'quantity' => 3, 'price_per_unit' => 1000],
                    ['waste_category_id' => $catB->id, 'quantity' => 5, 'price_per_unit' => 1000],
                ],
            );
            $this->fail('Expected exception');
        } catch (InvalidArgumentException) {
            // rollback expected - catA stock should be unchanged
        }

        $this->assertSame('10.000', (string) Inventory::firstWhere('waste_category_id', $catA->id)->stock);
        $this->assertSame('2.000', (string) Inventory::firstWhere('waste_category_id', $catB->id)->stock);
        $this->assertDatabaseCount('sales_transactions', 0);
    }

    public function test_requires_at_least_one_item(): void
    {
        $partner = Partner::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        app(SalesTransactionService::class)->create($partner, []);
    }
}
