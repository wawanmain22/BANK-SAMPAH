<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use App\Models\WasteItem;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProcessingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.processing.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_processing_via_component(): void
    {
        $admin = User::factory()->admin()->create();
        $item = WasteItem::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        app(InventoryService::class)->add(
            item: $item,
            source: InventoryService::SOURCE_SEDEKAH,
            quantity: 50,
            reason: 'adjustment',
        );

        $this->actingAs($admin);

        Livewire::test('pages::admin.processing.create')
            ->set('inputs.0.waste_item_id', $item->id)
            ->set('inputs.0.quantity', '8')
            ->call('addOutput')
            ->set('outputs.0.product_id', $product->id)
            ->set('outputs.0.quantity', '4')
            ->set('notes', 'Olah paving')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.processing.index'));

        $this->assertDatabaseHas('processing_transactions', [
            'total_input_weight' => '8.000',
            'created_by' => $admin->id,
        ]);

        $this->assertSame('4.000', (string) $product->refresh()->stock);
    }

    public function test_form_requires_at_least_one_input(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.processing.create')
            ->call('save')
            ->assertHasErrors(['inputs.0.waste_item_id', 'inputs.0.quantity']);
    }
}
