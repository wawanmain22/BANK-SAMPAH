<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\SavingTransaction;
use App\Models\User;
use App\Models\WasteItem;
use App\Models\WastePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SavingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function itemWithPrice(float $price = 3000): WasteItem
    {
        $item = WasteItem::factory()->create(['price_per_unit' => $price]);
        WastePrice::factory()->create([
            'waste_item_id' => $item->id,
            'price_per_unit' => $price,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        return $item->fresh('currentPrice');
    }

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.saving.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.saving.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_transaction_list(): void
    {
        $nasabah = User::factory()->nasabah()->create(['name' => 'Siti Aisyah']);
        SavingTransaction::factory()->create([
            'user_id' => $nasabah->id,
            'total_value' => 7500,
        ]);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.saving.index')
            ->assertSee('Siti Aisyah');
    }

    public function test_admin_can_create_transaction_via_form(): void
    {
        $nasabah = User::factory()->nasabah()->create(['is_member' => true]);
        $item = $this->itemWithPrice(4000);
        $admin = $this->admin();

        $this->actingAs($admin);

        Livewire::test('pages::admin.saving.create')
            ->set('user_id', $nasabah->id)
            ->set('items.0.waste_item_id', $item->id)
            ->set('items.0.quantity', '2.5')
            ->set('notes', 'Tes UI')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.saving.index'));

        $this->assertDatabaseHas('saving_transactions', [
            'user_id' => $nasabah->id,
            'total_value' => '10000.00',
            'total_weight' => '2.500',
            'created_by' => $admin->id,
        ]);
    }

    public function test_form_requires_user_and_at_least_one_item(): void
    {
        $this->actingAs($this->admin());

        Livewire::test('pages::admin.saving.create')
            ->call('save')
            ->assertHasErrors(['user_id', 'items.0.waste_item_id', 'items.0.quantity']);
    }

    public function test_can_add_and_remove_items_dynamically(): void
    {
        $this->actingAs($this->admin());

        $component = Livewire::test('pages::admin.saving.create');
        $this->assertCount(1, $component->get('items'));

        $component->call('addItem');
        $this->assertCount(2, $component->get('items'));

        $component->call('removeItem', 1);
        $this->assertCount(1, $component->get('items'));

        $component->call('removeItem', 0);
        $this->assertCount(1, $component->get('items'));
    }
}
