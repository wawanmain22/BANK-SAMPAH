<?php

declare(strict_types=1);

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
            ->set('code_prefix', 'PLB')
            ->set('description', 'Botol air mineral PET')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('waste_categories', [
            'name' => 'Plastik Botol',
            'code_prefix' => 'PLB',
            'is_active' => true,
        ]);
    }

    public function test_category_name_must_be_unique(): void
    {
        WasteCategory::factory()->create(['name' => 'Plastik', 'code_prefix' => 'PLX']);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-category.index')
            ->call('startCreating')
            ->set('name', 'Plastik')
            ->set('code_prefix', 'PLZ')
            ->set('is_active', true)
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_code_prefix_must_be_unique(): void
    {
        WasteCategory::factory()->create(['code_prefix' => 'KT']);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-category.index')
            ->call('startCreating')
            ->set('name', 'Kertas Daur Ulang')
            ->set('code_prefix', 'KT')
            ->set('is_active', true)
            ->call('save')
            ->assertHasErrors(['code_prefix']);
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

    public function test_admin_can_delete_empty_category(): void
    {
        $category = WasteCategory::factory()->create();

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.waste-category.index')
            ->call('confirmDelete', $category->id)
            ->call('delete');

        $this->assertDatabaseMissing('waste_categories', ['id' => $category->id]);
    }
}
