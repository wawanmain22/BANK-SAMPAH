<?php

namespace Tests\Feature\Admin;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.partner.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.partner.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_partner(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.partner.index')
            ->call('startCreating')
            ->set('name', 'CV Daur Ulang Sejahtera')
            ->set('type', 'pengepul')
            ->set('phone', '02112345678')
            ->set('email', 'contact@daurulang.test')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('partners', [
            'name' => 'CV Daur Ulang Sejahtera',
            'type' => 'pengepul',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_partner(): void
    {
        $partner = Partner::factory()->create(['name' => 'Old Name']);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.partner.index')
            ->call('startEditing', $partner->id)
            ->set('name', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('New Name', $partner->refresh()->name);
    }

    public function test_invalid_type_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.partner.index')
            ->call('startCreating')
            ->set('name', 'Test')
            ->set('type', 'invalid')
            ->call('save')
            ->assertHasErrors(['type']);
    }
}
