<?php

namespace Tests\Feature\Admin;

use App\Models\Balance;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_withdrawal_deducts_tersedia_and_creates_record(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        $admin = User::factory()->admin()->create();
        Balance::create(['user_id' => $nasabah->id, 'saldo_tersedia' => 50000]);

        $wd = app(BalanceService::class)->withdraw($nasabah, 20000, 'cash', $admin);

        $this->assertInstanceOf(WithdrawalRequest::class, $wd);
        $this->assertSame('20000.00', (string) $wd->amount);
        $this->assertSame('cash', $wd->method);
        $this->assertSame($admin->id, $wd->processed_by);

        $this->assertSame('30000.00', (string) $nasabah->balance()->first()->saldo_tersedia);

        $this->assertDatabaseHas('balance_histories', [
            'user_id' => $nasabah->id,
            'bucket' => 'tersedia',
            'type' => 'withdrawal',
            'amount' => '-20000.00',
            'balance_after' => '30000.00',
            'source_type' => WithdrawalRequest::class,
            'source_id' => $wd->id,
        ]);
    }

    public function test_transfer_withdrawal_stores_bank_meta(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        $admin = User::factory()->admin()->create();
        Balance::create(['user_id' => $nasabah->id, 'saldo_tersedia' => 100000]);

        $wd = app(BalanceService::class)->withdraw($nasabah, 75000, 'transfer', $admin, meta: [
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Siti Aisyah',
        ]);

        $this->assertSame('BCA', $wd->bank_name);
        $this->assertSame('1234567890', $wd->account_number);
    }

    public function test_cannot_withdraw_more_than_tersedia(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        Balance::create(['user_id' => $nasabah->id, 'saldo_tersedia' => 1000]);

        $this->expectException(InvalidArgumentException::class);
        app(BalanceService::class)->withdraw($nasabah, 5000, 'cash', User::factory()->admin()->create());
    }

    public function test_invalid_method_rejected(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        Balance::create(['user_id' => $nasabah->id, 'saldo_tersedia' => 5000]);

        $this->expectException(InvalidArgumentException::class);
        app(BalanceService::class)->withdraw($nasabah, 1000, 'crypto', User::factory()->admin()->create());
    }

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.withdrawal.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.withdrawal.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_withdrawal_via_component(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        Balance::create(['user_id' => $nasabah->id, 'saldo_tersedia' => 30000]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.withdrawal.index')
            ->call('startCreating')
            ->set('user_id', $nasabah->id)
            ->set('amount', '15000')
            ->set('method', 'cash')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('15000.00', (string) $nasabah->balance()->first()->saldo_tersedia);
        $this->assertDatabaseHas('withdrawal_requests', [
            'user_id' => $nasabah->id,
            'amount' => '15000.00',
            'method' => 'cash',
        ]);
    }

    public function test_transfer_requires_bank_fields(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        Balance::create(['user_id' => $nasabah->id, 'saldo_tersedia' => 30000]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.withdrawal.index')
            ->call('startCreating')
            ->set('user_id', $nasabah->id)
            ->set('amount', '15000')
            ->set('method', 'transfer')
            ->call('save')
            ->assertHasErrors(['bank_name', 'account_number', 'account_name']);
    }
}
