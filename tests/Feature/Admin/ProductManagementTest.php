<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.product.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.product.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_product(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.product.index')
            ->call('startCreating')
            ->set('name', 'Paving Block')
            ->set('unit', 'pcs')
            ->set('price', '5000')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Paving Block',
            'unit' => 'pcs',
            'price' => '5000.00',
        ]);
    }

    public function test_name_must_be_unique(): void
    {
        Product::factory()->create(['name' => 'Kompos']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.product.index')
            ->call('startCreating')
            ->set('name', 'Kompos')
            ->set('unit', 'kg')
            ->set('price', '1000')
            ->set('is_active', true)
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::factory()->create(['name' => 'Pot Plastik', 'price' => 1000]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.product.index')
            ->call('startEditing', $product->id)
            ->set('price', '1500')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('1500.00', (string) $product->refresh()->price);
    }
}
