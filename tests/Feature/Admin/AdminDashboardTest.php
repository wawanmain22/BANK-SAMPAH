<?php

namespace Tests\Feature\Admin;

use App\Models\Balance;
use App\Models\SavingTransaction;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_gated_by_role(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_shows_aggregate_stats(): void
    {
        User::factory()->nasabah()->count(3)->create();
        User::factory()->nasabah()->create(['is_member' => true, 'member_joined_at' => now()]);

        $nasabah = User::factory()->nasabah()->create();
        Balance::create([
            'user_id' => $nasabah->id,
            'saldo_tertahan' => 12500,
            'saldo_tersedia' => 7500,
        ]);

        SavingTransaction::factory()->create([
            'user_id' => $nasabah->id,
            'total_value' => 50000,
            'total_weight' => 10,
        ]);

        WithdrawalRequest::factory()->create([
            'user_id' => $nasabah->id,
            'amount' => 3000,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Rp 12.500')
            ->assertSee('Rp 7.500')
            ->assertSee('Rp 50.000')
            ->assertSee('Rp 3.000');
    }
}
