<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\WasteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SedekahManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.sedekah.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.sedekah.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_sedekah_via_component(): void
    {
        $admin = User::factory()->admin()->create();
        $item = WasteItem::factory()->create();

        $this->actingAs($admin);

        Livewire::test('pages::admin.sedekah.create')
            ->set('donor_name', 'Ibu Anonim')
            ->set('items.0.waste_item_id', $item->id)
            ->set('items.0.quantity', '4')
            ->set('notes', 'donasi minggu ini')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.sedekah.index'));

        $this->assertDatabaseHas('sedekah_transactions', [
            'donor_name' => 'Ibu Anonim',
            'total_weight' => '4.000',
            'created_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'waste_item_id' => $item->id,
            'source' => 'sedekah',
            'reason' => 'sedekah',
            'quantity' => '4.000',
        ]);
    }

    public function test_form_requires_at_least_one_item(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.sedekah.create')
            ->call('save')
            ->assertHasErrors(['items.0.waste_item_id', 'items.0.quantity']);
    }
}
