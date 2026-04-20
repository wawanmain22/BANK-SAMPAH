<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use App\Services\ProductInventoryService;
use App\Services\ProductSalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSaleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function seedStock(Product $p, float $qty): void
    {
        app(ProductInventoryService::class)->add(
            product: $p,
            quantity: $qty,
            reason: 'adjustment',
        );
    }

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.product-sale.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.product-sale.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_sale_via_form(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['price' => 3500, 'stock' => 0]);
        $this->seedStock($product, 20);

        $this->actingAs($admin);

        Livewire::test('pages::admin.product-sale.create')
            ->set('buyer_name', 'Bu Warti')
            ->set('buyer_phone', '081234567890')
            ->set('items.0.product_id', $product->id)
            ->set('items.0.quantity', '3')
            ->set('items.0.price_per_unit', '3500')
            ->set('payment_method', 'cash')
            ->set('payment_status', 'paid')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.product-sale.index'));

        $this->assertDatabaseHas('product_sales', [
            'buyer_name' => 'Bu Warti',
            'buyer_phone' => '081234567890',
            'total_value' => '10500.00',
            'payment_status' => 'paid',
            'created_by' => $admin->id,
        ]);

        $this->assertSame('17.000', (string) $product->refresh()->stock);
    }

    public function test_form_requires_name_phone_and_items(): void
    {
        $this->actingAs($this->admin());

        Livewire::test('pages::admin.product-sale.create')
            ->call('save')
            ->assertHasErrors([
                'buyer_name',
                'buyer_phone',
                'items.0.product_id',
                'items.0.quantity',
                'items.0.price_per_unit',
            ]);
    }

    public function test_admin_can_mark_pending_sale_as_paid(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['stock' => 0]);
        $this->seedStock($product, 10);

        $sale = app(ProductSalesService::class)->create(
            buyerName: 'Reseller',
            buyerPhone: '081',
            items: [['product_id' => $product->id, 'quantity' => 1, 'price_per_unit' => 5000]],
            paymentStatus: 'pending',
        );

        $this->actingAs($admin);

        Livewire::test('pages::admin.product-sale.index')
            ->call('markPaid', $sale->id);

        $this->assertSame('paid', $sale->fresh()->payment_status);
    }

    public function test_buyer_selection_autofills_name_and_phone(): void
    {
        $this->actingAs($this->admin());
        $nasabah = User::factory()->nasabah()->create([
            'name' => 'Nasabah A',
            'phone' => '085555',
        ]);

        $component = Livewire::test('pages::admin.product-sale.create')
            ->set('buyer_user_id', $nasabah->id);

        $this->assertSame('Nasabah A', $component->get('buyer_name'));
        $this->assertSame('085555', $component->get('buyer_phone'));
    }
}
