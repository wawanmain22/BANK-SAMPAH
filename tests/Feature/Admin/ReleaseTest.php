<?php

namespace Tests\Feature\Admin;

use App\Models\Balance;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class ReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_moves_tertahan_to_tersedia(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        $admin = User::factory()->admin()->create();
        Balance::create(['user_id' => $nasabah->id, 'saldo_tertahan' => 10000, 'saldo_tersedia' => 500]);

        app(BalanceService::class)->release($nasabah, 7500, $admin, 'dari mitra A');

        $balance = $nasabah->balance()->first();
        $this->assertSame('2500.00', (string) $balance->saldo_tertahan);
        $this->assertSame('8000.00', (string) $balance->saldo_tersedia);

        $this->assertDatabaseHas('balance_histories', [
            'user_id' => $nasabah->id,
            'bucket' => 'tertahan',
            'type' => 'release',
            'amount' => '-7500.00',
            'balance_after' => '2500.00',
        ]);
        $this->assertDatabaseHas('balance_histories', [
            'user_id' => $nasabah->id,
            'bucket' => 'tersedia',
            'type' => 'release',
            'amount' => '7500.00',
            'balance_after' => '8000.00',
        ]);
    }

    public function test_cannot_release_more_than_tertahan(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        Balance::create(['user_id' => $nasabah->id, 'saldo_tertahan' => 1000]);

        $this->expectException(InvalidArgumentException::class);
        app(BalanceService::class)->release($nasabah, 5000, User::factory()->admin()->create());
    }

    public function test_release_page_gated_by_role(): void
    {
        $this->get(route('admin.release.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.release.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_nasabah_with_tertahan_positive(): void
    {
        $withTertahan = User::factory()->nasabah()->create(['name' => 'Ada Positif']);
        Balance::create(['user_id' => $withTertahan->id, 'saldo_tertahan' => 5000]);

        $withoutTertahan = User::factory()->nasabah()->create(['name' => 'Tidak Ada']);
        Balance::create(['user_id' => $withoutTertahan->id, 'saldo_tertahan' => 0]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.release.index')
            ->assertSee('Ada Positif')
            ->assertDontSee('Tidak Ada');
    }

    public function test_admin_can_release_via_component(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        Balance::create(['user_id' => $nasabah->id, 'saldo_tertahan' => 10000]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.release.index')
            ->call('startRelease', $nasabah->id)
            ->set('amount', '4000')
            ->set('notes', 'sudah dibayar mitra')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('6000.00', (string) $nasabah->balance()->first()->saldo_tertahan);
        $this->assertSame('4000.00', (string) $nasabah->balance()->first()->saldo_tersedia);
    }
}
