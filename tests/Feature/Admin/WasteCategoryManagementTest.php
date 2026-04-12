<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\WasteCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WasteCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.waste-category.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.waste-category.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_category(): void
    {
        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-category.index')
            ->call('startCreating')
            ->set('name', 'Plastik Botol')
            ->set('unit', 'kg')
            ->set('description', 'Botol air mineral PET')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('waste_categories', [
            'name' => 'Plastik Botol',
            'unit' => 'kg',
            'is_active' => true,
        ]);
    }

    public function test_category_name_must_be_unique(): void
    {
        WasteCategory::factory()->create(['name' => 'Plastik']);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-category.index')
            ->call('startCreating')
            ->set('name', 'Plastik')
            ->set('unit', 'kg')
            ->set('is_active', true)
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_admin_can_update_category(): void
    {
        $category = WasteCategory::factory()->create(['name' => 'Kertas']);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-category.index')
            ->call('startEditing', $category->id)
            ->set('name', 'Kertas HVS')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Kertas HVS', $category->refresh()->name);
    }

    public function test_admin_can_delete_category(): void
    {
        $category = WasteCategory::factory()->create();

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-category.index')
            ->call('confirmDelete', $category->id)
            ->call('delete');

        $this->assertDatabaseMissing('waste_categories', ['id' => $category->id]);
    }
}
