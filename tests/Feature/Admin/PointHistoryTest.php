<?php

namespace Tests\Feature\Admin;

use App\Models\PointHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PointHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.point-history.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.point-history.index'))
            ->assertForbidden();
    }

    public function test_admin_can_see_histories(): void
    {
        $nasabah = User::factory()->nasabah()->create(['name' => 'Siti Member']);
        PointHistory::create([
            'user_id' => $nasabah->id,
            'type' => 'earn',
            'points' => 25,
            'balance_after' => 25,
            'description' => 'Dari transaksi #1',
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.point-history.index')
            ->assertSee('Siti Member')
            ->assertSee('+25');
    }

    public function test_can_filter_by_user(): void
    {
        $alice = User::factory()->nasabah()->create(['name' => 'Alice']);
        $bob = User::factory()->nasabah()->create(['name' => 'Bob']);

        PointHistory::create(['user_id' => $alice->id, 'type' => 'earn', 'points' => 10, 'balance_after' => 10]);
        PointHistory::create(['user_id' => $bob->id, 'type' => 'earn', 'points' => 20, 'balance_after' => 20]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.point-history.index')
            ->set('user_id', $alice->id)
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }
}
