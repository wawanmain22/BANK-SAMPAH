<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\ProcessingTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\WasteCategory;
use App\Services\InventoryService;
use App\Services\ProcessingTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProcessingTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_decrements_inventory_and_increments_product_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $category = WasteCategory::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        app(InventoryService::class)->add($category, 50, 'adjustment');

        $transaction = app(ProcessingTransactionService::class)->create(
            inputs: [['waste_category_id' => $category->id, 'quantity' => 10]],
            outputs: [['product_id' => $product->id, 'quantity' => 3]],
            notes: 'Olah kompos',
            createdBy: $admin,
        );

        $this->assertSame('10.000', (string) $transaction->total_input_weight);
        $this->assertCount(1, $transaction->inputs);
        $this->assertCount(1, $transaction->outputs);

        $this->assertSame('40.000', (string) Inventory::firstWhere('waste_category_id', $category->id)->stock);
        $this->assertSame('3.000', (string) $product->refresh()->stock);

        $this->assertDatabaseHas('inventory_movements', [
            'waste_category_id' => $category->id,
            'direction' => 'out',
            'reason' => 'process',
            'quantity' => '10.000',
            'source_type' => ProcessingTransaction::class,
        ]);
    }

    public function test_processing_can_have_no_outputs(): void
    {
        $category = WasteCategory::factory()->create();
        app(InventoryService::class)->add($category, 20, 'adjustment');

        $transaction = app(ProcessingTransactionService::class)->create(
            inputs: [['waste_category_id' => $category->id, 'quantity' => 5]],
        );

        $this->assertCount(0, $transaction->outputs);
        $this->assertSame('15.000', (string) Inventory::firstWhere('waste_category_id', $category->id)->stock);
    }

    public function test_processing_fails_when_stock_insufficient(): void
    {
        $category = WasteCategory::factory()->create();
        app(InventoryService::class)->add($category, 2, 'adjustment');

        $this->expectException(InvalidArgumentException::class);

        app(ProcessingTransactionService::class)->create(
            inputs: [['waste_category_id' => $category->id, 'quantity' => 10]],
        );
    }

    public function test_processing_requires_at_least_one_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(ProcessingTransactionService::class)->create(inputs: []);
    }

    public function test_multiple_inputs_and_outputs_accumulate(): void
    {
        $catA = WasteCategory::factory()->create();
        $catB = WasteCategory::factory()->create();
        $productA = Product::factory()->create(['stock' => 0]);
        $productB = Product::factory()->create(['stock' => 0]);

        app(InventoryService::class)->add($catA, 100, 'adjustment');
        app(InventoryService::class)->add($catB, 100, 'adjustment');

        app(ProcessingTransactionService::class)->create(
            inputs: [
                ['waste_category_id' => $catA->id, 'quantity' => 30],
                ['waste_category_id' => $catB->id, 'quantity' => 20],
            ],
            outputs: [
                ['product_id' => $productA->id, 'quantity' => 5],
                ['product_id' => $productB->id, 'quantity' => 8],
            ],
        );

        $this->assertSame('70.000', (string) Inventory::firstWhere('waste_category_id', $catA->id)->stock);
        $this->assertSame('80.000', (string) Inventory::firstWhere('waste_category_id', $catB->id)->stock);
        $this->assertSame('5.000', (string) $productA->refresh()->stock);
        $this->assertSame('8.000', (string) $productB->refresh()->stock);
    }
}
