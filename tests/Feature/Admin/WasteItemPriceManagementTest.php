<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\WasteItem;
use App\Models\WastePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WasteItemPriceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_set_new_price_via_waste_item_page(): void
    {
        $item = WasteItem::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin);

        Livewire::test('pages::admin.waste-item.index')
            ->call('startSettingPrice', $item->id)
            ->set('price_new', '5000')
            ->set('price_effective_from', now()->toDateString())
            ->set('price_notes', 'Harga pasar naik')
            ->call('savePrice')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('waste_prices', [
            'waste_item_id' => $item->id,
            'price_per_unit' => '5000.00',
            'created_by' => $admin->id,
            'notes' => 'Harga pasar naik',
        ]);

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

        Livewire::test('pages::admin.waste-item.index')
            ->call('showHistory', $item->id)
            ->assertSee('Harga awal');
    }

    public function test_editing_item_does_not_require_price_field(): void
    {
        $item = WasteItem::factory()->create();

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-item.index')
            ->call('startEditing', $item->id)
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Updated Name', $item->refresh()->name);
    }
}
