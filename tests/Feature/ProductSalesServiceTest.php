<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductMovement;
use App\Models\ProductSale;
use App\Models\User;
use App\Services\ProductInventoryService;
use App\Services\ProductSalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProductSalesServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedStock(Product $product, float $qty): void
    {
        app(ProductInventoryService::class)->add(
            product: $product,
            quantity: $qty,
            reason: 'adjustment',
        );
    }

    public function test_creates_sale_snapshot_and_decrements_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['price' => 5000, 'stock' => 0]);

        $this->seedStock($product, 10);

        $sale = app(ProductSalesService::class)->create(
            buyerName: 'Bu Warti',
            buyerPhone: '081234567890',
            items: [['product_id' => $product->id, 'quantity' => 3, 'price_per_unit' => 5000]],
            paymentMethod: 'cash',
            paymentStatus: 'paid',
            notes: 'Demo',
            createdBy: $admin,
        );

        $this->assertSame('Bu Warti', $sale->buyer_name);
        $this->assertSame('081234567890', $sale->buyer_phone);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('cash', $sale->payment_method);
        $this->assertSame('15000.00', (string) $sale->total_value);
        $this->assertSame('3.000', (string) $sale->total_quantity);
        $this->assertCount(1, $sale->items);

        $item = $sale->items->first();
        $this->assertSame('5000.00', (string) $item->price_per_unit_snapshot);
        $this->assertSame('15000.00', (string) $item->subtotal);
        $this->assertSame($product->name, $item->product_name_snapshot);

        $this->assertSame('7.000', (string) $product->refresh()->stock);

        $this->assertDatabaseHas('product_movements', [
            'product_id' => $product->id,
            'direction' => 'out',
            'reason' => 'sale',
            'quantity' => '3.000',
            'stock_after' => '7.000',
            'source_ref_type' => ProductSale::class,
            'source_ref_id' => $sale->id,
        ]);
    }

    public function test_cannot_sell_more_than_stock(): void
    {
        $product = Product::factory()->create(['stock' => 0]);
        $this->seedStock($product, 2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak cukup');

        app(ProductSalesService::class)->create(
            buyerName: 'X',
            buyerPhone: '08',
            items: [['product_id' => $product->id, 'quantity' => 5]],
        );
    }

    public function test_rollback_on_stock_shortage(): void
    {
        $a = Product::factory()->create(['stock' => 0]);
        $b = Product::factory()->create(['stock' => 0]);
        $this->seedStock($a, 10);
        $this->seedStock($b, 1);

        try {
            app(ProductSalesService::class)->create(
                buyerName: 'Rollback Test',
                buyerPhone: '08',
                items: [
                    ['product_id' => $a->id, 'quantity' => 2, 'price_per_unit' => 1000],
                    ['product_id' => $b->id, 'quantity' => 5, 'price_per_unit' => 1000],
                ],
            );
            $this->fail('Expected exception');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->assertSame('10.000', (string) $a->refresh()->stock);
        $this->assertSame('1.000', (string) $b->refresh()->stock);
        $this->assertDatabaseCount('product_sales', 0);
        // The sale-reason movement from product A should have rolled back too.
        $this->assertSame(0, ProductMovement::where('reason', 'sale')->count());
    }

    public function test_requires_buyer_name_and_phone(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->expectException(InvalidArgumentException::class);
        app(ProductSalesService::class)->create(
            buyerName: '',
            buyerPhone: '08',
            items: [['product_id' => $product->id, 'quantity' => 1]],
        );
    }

    public function test_requires_items(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(ProductSalesService::class)->create(
            buyerName: 'X',
            buyerPhone: '08',
            items: [],
        );
    }

    public function test_rejects_invalid_payment_method(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->expectException(InvalidArgumentException::class);
        app(ProductSalesService::class)->create(
            buyerName: 'X',
            buyerPhone: '08',
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: 'bitcoin',
        );
    }

    public function test_mark_paid_flips_status(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $service = app(ProductSalesService::class);

        $sale = $service->create(
            buyerName: 'Resaler',
            buyerPhone: '08',
            items: [['product_id' => $product->id, 'quantity' => 1, 'price_per_unit' => 5000]],
            paymentStatus: 'pending',
        );

        $this->assertSame('pending', $sale->payment_status);

        $service->markPaid($sale);

        $this->assertSame('paid', $sale->fresh()->payment_status);
    }

    public function test_processing_output_creates_product_movement(): void
    {
        $item = \App\Models\WasteItem::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        app(\App\Services\InventoryService::class)->add(
            item: $item,
            source: \App\Services\InventoryService::SOURCE_SEDEKAH,
            quantity: 10,
            reason: 'adjustment',
        );

        app(\App\Services\ProcessingTransactionService::class)->create(
            inputs: [['waste_item_id' => $item->id, 'quantity' => 2]],
            outputs: [['product_id' => $product->id, 'quantity' => 5]],
        );

        $this->assertSame('5.000', (string) $product->refresh()->stock);

        $this->assertDatabaseHas('product_movements', [
            'product_id' => $product->id,
            'direction' => 'in',
            'reason' => 'process',
            'quantity' => '5.000',
            'stock_after' => '5.000',
        ]);
    }
}
