<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WastePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WastePriceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.waste-price.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.waste-price.index'))
            ->assertForbidden();
    }

    public function test_only_active_categories_are_listed(): void
    {
        $active = WasteCategory::factory()->create(['name' => 'Plastik']);
        $inactive = WasteCategory::factory()->inactive()->create(['name' => 'Kaca']);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-price.index')
            ->assertSee('Plastik')
            ->assertDontSee('Kaca');
    }

    public function test_admin_can_set_new_price(): void
    {
        $category = WasteCategory::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin);

        Livewire::test('pages::admin.waste-price.index')
            ->call('startSettingPrice', $category->id)
            ->set('price_per_unit', '5000')
            ->set('effective_from', now()->toDateString())
            ->set('notes', 'Harga pasar naik')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('waste_prices', [
            'waste_category_id' => $category->id,
            'price_per_unit' => '5000.00',
            'created_by' => $admin->id,
            'notes' => 'Harga pasar naik',
        ]);
    }

    public function test_current_price_returns_latest_effective(): void
    {
        $category = WasteCategory::factory()->create();

        WastePrice::factory()->create([
            'waste_category_id' => $category->id,
            'price_per_unit' => 1000,
            'effective_from' => now()->subDays(10)->toDateString(),
        ]);
        WastePrice::factory()->create([
            'waste_category_id' => $category->id,
            'price_per_unit' => 2000,
            'effective_from' => now()->subDay()->toDateString(),
        ]);
        WastePrice::factory()->create([
            'waste_category_id' => $category->id,
            'price_per_unit' => 3000,
            'effective_from' => now()->addDay()->toDateString(),
        ]);

        $this->assertSame('2000.00', (string) $category->currentPrice()->value('price_per_unit'));
    }

    public function test_history_modal_loads_prices_for_category(): void
    {
        $category = WasteCategory::factory()->create(['name' => 'Plastik Botol']);
        WastePrice::factory()->create([
            'waste_category_id' => $category->id,
            'price_per_unit' => 1500,
            'notes' => 'Harga awal',
        ]);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-price.index')
            ->call('showHistory', $category->id)
            ->assertSee('Harga awal');
    }
}
