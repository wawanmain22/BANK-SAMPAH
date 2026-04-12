<?php

namespace Tests\Feature\Admin;

use App\Models\Partner;
use App\Models\User;
use App\Models\WasteCategory;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.sales.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_sale_via_component(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = Partner::factory()->create();
        $category = WasteCategory::factory()->create();
        app(InventoryService::class)->add($category, 20, 'adjustment');

        $this->actingAs($admin);

        Livewire::test('pages::admin.sales.create')
            ->set('partner_id', $partner->id)
            ->set('items.0.waste_category_id', $category->id)
            ->set('items.0.quantity', '5')
            ->set('items.0.price_per_unit', '4500')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.sales.index'));

        $this->assertDatabaseHas('sales_transactions', [
            'partner_id' => $partner->id,
            'total_value' => '22500.00',
            'total_weight' => '5.000',
            'created_by' => $admin->id,
        ]);
    }

    public function test_form_requires_partner_and_items(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.sales.create')
            ->call('save')
            ->assertHasErrors(['partner_id', 'items.0.waste_category_id', 'items.0.quantity', 'items.0.price_per_unit']);
    }
}
