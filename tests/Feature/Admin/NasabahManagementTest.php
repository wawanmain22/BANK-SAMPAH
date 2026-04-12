<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NasabahManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_nasabah_index_is_gated_by_role(): void
    {
        $this->get(route('admin.nasabah.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.nasabah.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_only_nasabah_in_list(): void
    {
        $nasabahA = User::factory()->nasabah()->create(['name' => 'Siti Aisyah']);
        $nasabahB = User::factory()->nasabah()->create(['name' => 'Budi Pekerti']);
        $owner = User::factory()->owner()->create(['name' => 'Pak Toni']);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.nasabah.index')
            ->assertSee('Siti Aisyah')
            ->assertSee('Budi Pekerti')
            ->assertDontSee('Pak Toni');
    }

    public function test_admin_can_create_nasabah(): void
    {
        $this->actingAs($this->admin());

        Livewire::test('pages::admin.nasabah.index')
            ->call('startCreating')
            ->set('name', 'Nia Nasabah')
            ->set('email', 'nia@banksampah.test')
            ->set('phone', '08123456789')
            ->set('address', 'Jl. Melati 12')
            ->set('is_member', true)
            ->set('member_joined_at', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'nia@banksampah.test',
            'name' => 'Nia Nasabah',
            'phone' => '08123456789',
            'role' => UserRole::Nasabah->value,
            'is_member' => true,
        ]);
    }

    public function test_creating_member_requires_join_date(): void
    {
        $this->actingAs($this->admin());

        Livewire::test('pages::admin.nasabah.index')
            ->call('startCreating')
            ->set('name', 'Nia Nasabah')
            ->set('email', 'nia2@banksampah.test')
            ->set('is_member', true)
            ->set('member_joined_at', null)
            ->call('save')
            ->assertHasErrors(['member_joined_at']);
    }

    public function test_admin_can_update_nasabah(): void
    {
        $nasabah = User::factory()->nasabah()->create([
            'name' => 'Budi',
            'email' => 'budi@banksampah.test',
        ]);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.nasabah.index')
            ->call('startEditing', $nasabah->id)
            ->set('name', 'Budi Pekerti')
            ->set('is_member', true)
            ->set('member_joined_at', '2026-01-15')
            ->call('save')
            ->assertHasNoErrors();

        $nasabah->refresh();
        $this->assertSame('Budi Pekerti', $nasabah->name);
        $this->assertTrue($nasabah->is_member);
        $this->assertSame('2026-01-15', $nasabah->member_joined_at->format('Y-m-d'));
    }

    public function test_admin_can_delete_nasabah(): void
    {
        $nasabah = User::factory()->nasabah()->create();

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.nasabah.index')
            ->call('confirmDelete', $nasabah->id)
            ->call('delete');

        $this->assertDatabaseMissing('users', ['id' => $nasabah->id]);
    }

    public function test_cannot_edit_non_nasabah_user_through_component(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($this->admin());

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test('pages::admin.nasabah.index')
            ->call('startEditing', $owner->id);
    }

    public function test_search_filters_by_name_or_phone(): void
    {
        User::factory()->nasabah()->create(['name' => 'Siti', 'phone' => '08111']);
        User::factory()->nasabah()->create(['name' => 'Budi', 'phone' => '08222']);

        $this->actingAs($this->admin());

        Livewire::test('pages::admin.nasabah.index')
            ->set('search', 'Siti')
            ->assertSee('Siti')
            ->assertDontSee('Budi');
    }
}
