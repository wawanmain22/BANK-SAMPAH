<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\ProcessingTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\WasteItem;
use App\Services\InventoryService;
use App\Services\ProcessingTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProcessingTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedSedekah(WasteItem $item, float $qty): void
    {
        app(InventoryService::class)->add(
            item: $item,
            source: InventoryService::SOURCE_SEDEKAH,
            quantity: $qty,
            reason: 'adjustment',
        );
    }

    private function seedNabung(WasteItem $item, float $qty): void
    {
        app(InventoryService::class)->add(
            item: $item,
            source: InventoryService::SOURCE_NABUNG,
            quantity: $qty,
            reason: 'adjustment',
        );
    }

    public function test_processing_decrements_sedekah_pool_and_increments_product(): void
    {
        $admin = User::factory()->admin()->create();
        $item = WasteItem::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        $this->seedSedekah($item, 50);

        $transaction = app(ProcessingTransactionService::class)->create(
            inputs: [['waste_item_id' => $item->id, 'quantity' => 10]],
            outputs: [['product_id' => $product->id, 'quantity' => 3]],
            notes: 'Olah kompos',
            createdBy: $admin,
        );

        $this->assertSame('10.000', (string) $transaction->total_input_weight);
        $this->assertCount(1, $transaction->inputs);
        $this->assertCount(1, $transaction->outputs);

        $sedekah = Inventory::where('waste_item_id', $item->id)
            ->where('source', InventoryService::SOURCE_SEDEKAH)->first();
        $this->assertSame('40.000', (string) $sedekah->stock);
        $this->assertSame('3.000', (string) $product->refresh()->stock);

        $this->assertDatabaseHas('inventory_movements', [
            'waste_item_id' => $item->id,
            'source' => 'sedekah',
            'direction' => 'out',
            'reason' => 'process',
            'quantity' => '10.000',
            'source_ref_type' => ProcessingTransaction::class,
        ]);
    }

    public function test_processing_cannot_draw_from_nabung_pool(): void
    {
        $item = WasteItem::factory()->create();
        // Nabung has stock, sedekah empty.
        $this->seedNabung($item, 50);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak cukup');

        app(ProcessingTransactionService::class)->create(
            inputs: [['waste_item_id' => $item->id, 'quantity' => 5]],
        );

        // Nabung pool must remain untouched.
        $nabung = Inventory::where('waste_item_id', $item->id)
            ->where('source', InventoryService::SOURCE_NABUNG)->first();
        $this->assertSame('50.000', (string) $nabung->stock);
    }

    public function test_processing_can_have_no_outputs(): void
    {
        $item = WasteItem::factory()->create();
        $this->seedSedekah($item, 20);

        $transaction = app(ProcessingTransactionService::class)->create(
            inputs: [['waste_item_id' => $item->id, 'quantity' => 5]],
        );

        $this->assertCount(0, $transaction->outputs);
        $sedekah = Inventory::where('waste_item_id', $item->id)
            ->where('source', InventoryService::SOURCE_SEDEKAH)->first();
        $this->assertSame('15.000', (string) $sedekah->stock);
    }

    public function test_processing_fails_when_stock_insufficient(): void
    {
        $item = WasteItem::factory()->create();
        $this->seedSedekah($item, 2);

        $this->expectException(InvalidArgumentException::class);

        app(ProcessingTransactionService::class)->create(
            inputs: [['waste_item_id' => $item->id, 'quantity' => 10]],
        );
    }

    public function test_processing_requires_at_least_one_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(ProcessingTransactionService::class)->create(inputs: []);
    }

    public function test_multiple_inputs_and_outputs_accumulate(): void
    {
        $a = WasteItem::factory()->create();
        $b = WasteItem::factory()->create();
        $productA = Product::factory()->create(['stock' => 0]);
        $productB = Product::factory()->create(['stock' => 0]);

        $this->seedSedekah($a, 100);
        $this->seedSedekah($b, 100);

        app(ProcessingTransactionService::class)->create(
            inputs: [
                ['waste_item_id' => $a->id, 'quantity' => 30],
                ['waste_item_id' => $b->id, 'quantity' => 20],
            ],
            outputs: [
                ['product_id' => $productA->id, 'quantity' => 5],
                ['product_id' => $productB->id, 'quantity' => 8],
            ],
        );

        $stockA = Inventory::where('waste_item_id', $a->id)
            ->where('source', InventoryService::SOURCE_SEDEKAH)->value('stock');
        $stockB = Inventory::where('waste_item_id', $b->id)
            ->where('source', InventoryService::SOURCE_SEDEKAH)->value('stock');

        $this->assertSame('70.000', (string) $stockA);
        $this->assertSame('80.000', (string) $stockB);
        $this->assertSame('5.000', (string) $productA->refresh()->stock);
        $this->assertSame('8.000', (string) $productB->refresh()->stock);
    }
}
