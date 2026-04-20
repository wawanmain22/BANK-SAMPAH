<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Partner;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Models\WasteItem;
use App\Services\InventoryService;
use App\Services\SalesTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SalesTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedNabung(WasteItem $item, float $qty): void
    {
        app(InventoryService::class)->add(
            item: $item,
            source: InventoryService::SOURCE_NABUNG,
            quantity: $qty,
            reason: 'adjustment',
        );
    }

    private function seedSedekah(WasteItem $item, float $qty): void
    {
        app(InventoryService::class)->add(
            item: $item,
            source: InventoryService::SOURCE_SEDEKAH,
            quantity: $qty,
            reason: 'adjustment',
        );
    }

    public function test_sale_decrements_nabung_pool_and_records(): void
    {
        $partner = Partner::factory()->create(['name' => 'CV Pengepul Jaya']);
        $admin = User::factory()->admin()->create();
        $item = WasteItem::factory()->create();

        $this->seedNabung($item, 20);

        $transaction = app(SalesTransactionService::class)->create(
            $partner,
            [['waste_item_id' => $item->id, 'quantity' => 7.5, 'price_per_unit' => 4000]],
            notes: 'Kirim siang',
            createdBy: $admin,
        );

        $this->assertSame('30000.00', (string) $transaction->total_value);
        $this->assertSame('7.500', (string) $transaction->total_weight);
        $this->assertSame($partner->id, $transaction->partner_id);

        $nabung = Inventory::where('waste_item_id', $item->id)
            ->where('source', InventoryService::SOURCE_NABUNG)
            ->first();
        $this->assertSame('12.500', (string) $nabung->stock);

        $this->assertDatabaseHas('inventory_movements', [
            'waste_item_id' => $item->id,
            'source' => 'nabung',
            'direction' => 'out',
            'reason' => 'sale',
            'quantity' => '7.500',
            'stock_after' => '12.500',
            'source_ref_type' => SalesTransaction::class,
        ]);
    }

    public function test_sale_cannot_draw_from_sedekah_pool(): void
    {
        $partner = Partner::factory()->create();
        $item = WasteItem::factory()->create();

        // Sedekah has stock, nabung empty.
        $this->seedSedekah($item, 20);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak cukup');

        app(SalesTransactionService::class)->create(
            $partner,
            [['waste_item_id' => $item->id, 'quantity' => 5, 'price_per_unit' => 1000]],
        );

        // Sedekah pool must remain untouched.
        $sedekah = Inventory::where('waste_item_id', $item->id)
            ->where('source', InventoryService::SOURCE_SEDEKAH)
            ->first();
        $this->assertSame('20.000', (string) $sedekah->stock);
    }

    public function test_cannot_sell_more_than_nabung_stock(): void
    {
        $partner = Partner::factory()->create();
        $item = WasteItem::factory()->create();
        $this->seedNabung($item, 5);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak cukup');

        app(SalesTransactionService::class)->create(
            $partner,
            [['waste_item_id' => $item->id, 'quantity' => 10, 'price_per_unit' => 1000]],
        );
    }

    public function test_rollback_if_one_item_exceeds_stock(): void
    {
        $partner = Partner::factory()->create();
        $a = WasteItem::factory()->create();
        $b = WasteItem::factory()->create();
        $this->seedNabung($a, 10);
        $this->seedNabung($b, 2);

        try {
            app(SalesTransactionService::class)->create(
                $partner,
                [
                    ['waste_item_id' => $a->id, 'quantity' => 3, 'price_per_unit' => 1000],
                    ['waste_item_id' => $b->id, 'quantity' => 5, 'price_per_unit' => 1000],
                ],
            );
            $this->fail('Expected exception');
        } catch (InvalidArgumentException) {
            // rollback expected
        }

        $stockA = Inventory::where('waste_item_id', $a->id)
            ->where('source', InventoryService::SOURCE_NABUNG)->value('stock');
        $stockB = Inventory::where('waste_item_id', $b->id)
            ->where('source', InventoryService::SOURCE_NABUNG)->value('stock');

        $this->assertSame('10.000', (string) $stockA);
        $this->assertSame('2.000', (string) $stockB);
        $this->assertDatabaseCount('sales_transactions', 0);
    }

    public function test_requires_at_least_one_item(): void
    {
        $partner = Partner::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        app(SalesTransactionService::class)->create($partner, []);
    }
}
