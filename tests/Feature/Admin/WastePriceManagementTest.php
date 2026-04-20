<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\WasteItem;
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

    public function test_only_active_items_are_listed(): void
    {
        WasteItem::factory()->create(['name' => 'Dus Bersih']);
        WasteItem::factory()->inactive()->create(['name' => 'Sendal Karet']);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-price.index')
            ->assertSee('Dus Bersih')
            ->assertDontSee('Sendal Karet');
    }

    public function test_admin_can_set_new_price(): void
    {
        $item = WasteItem::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin);

        Livewire::test('pages::admin.waste-price.index')
            ->call('startSettingPrice', $item->id)
            ->set('price_per_unit', '5000')
            ->set('effective_from', now()->toDateString())
            ->set('notes', 'Harga pasar naik')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('waste_prices', [
            'waste_item_id' => $item->id,
            'price_per_unit' => '5000.00',
            'created_by' => $admin->id,
            'notes' => 'Harga pasar naik',
        ]);

        // Denormalized current price on item is synced.
        $this->assertSame('5000.00', (string) $item->refresh()->price_per_unit);
    }

    public function test_current_price_returns_latest_effective(): void
    {
        $item = WasteItem::factory()->create();

        WastePrice::factory()->create([
            'waste_item_id' => $item->id,
            'price_per_unit' => 1000,
            'effective_from' => now()->subDays(10)->toDateString(),
        ]);
        WastePrice::factory()->create([
            'waste_item_id' => $item->id,
            'price_per_unit' => 2000,
            'effective_from' => now()->subDay()->toDateString(),
        ]);
        WastePrice::factory()->create([
            'waste_item_id' => $item->id,
            'price_per_unit' => 3000,
            'effective_from' => now()->addDay()->toDateString(),
        ]);

        $this->assertSame('2000.00', (string) $item->currentPrice()->value('price_per_unit'));
    }

    public function test_history_modal_loads_prices_for_item(): void
    {
        $item = WasteItem::factory()->create(['name' => 'PET Botol Bersih']);
        WastePrice::factory()->create([
            'waste_item_id' => $item->id,
            'price_per_unit' => 1500,
            'notes' => 'Harga awal',
        ]);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-price.index')
            ->call('showHistory', $item->id)
            ->assertSee('Harga awal');
    }
}
