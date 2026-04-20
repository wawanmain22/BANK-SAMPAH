<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\PointCashOut;
use App\Models\PointRule;
use App\Models\User;
use App\Services\PointCashOutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PointCashOutServiceTest extends TestCase
{
    use RefreshDatabase;

    private function activeRule(float $rupiahPerPoint = 1000): PointRule
    {
        return PointRule::factory()->create([
            'points_per_rupiah' => 0.001,
            'rupiah_per_point' => $rupiahPerPoint,
            'effective_from' => now()->subDay()->toDateString(),
        ]);
    }

    private function member(int $startingPoints = 100): User
    {
        $user = User::factory()->nasabah()->create([
            'is_member' => true,
            'member_joined_at' => now(),
        ]);
        Balance::create([
            'user_id' => $user->id,
            'points' => $startingPoints,
            'saldo_tertahan' => 0,
            'saldo_tersedia' => 0,
        ]);

        return $user;
    }

    public function test_converts_points_to_saldo_tersedia(): void
    {
        $this->activeRule(1000);
        $nasabah = $this->member(50);
        $admin = User::factory()->admin()->create();

        $cashOut = app(PointCashOutService::class)->create(
            nasabah: $nasabah,
            pointsUsed: 30,
            processedBy: $admin,
            notes: 'Cashout demo',
        );

        $this->assertInstanceOf(PointCashOut::class, $cashOut);
        $this->assertSame(30, $cashOut->points_used);
        $this->assertSame('1000.00', (string) $cashOut->rate_snapshot);
        $this->assertSame('30000.00', (string) $cashOut->cash_amount);

        $balance = $nasabah->balance()->first();
        $this->assertSame(20, (int) $balance->points);
        $this->assertSame('30000.00', (string) $balance->saldo_tersedia);

        $this->assertDatabaseHas('balance_histories', [
            'user_id' => $nasabah->id,
            'bucket' => 'tersedia',
            'type' => 'point_cashout',
            'amount' => '30000.00',
        ]);

        $this->assertDatabaseHas('point_histories', [
            'user_id' => $nasabah->id,
            'type' => 'redeem',
            'points' => -30,
        ]);
    }

    public function test_rejects_non_member(): void
    {
        $this->activeRule();
        $nasabah = User::factory()->nasabah()->create(['is_member' => false]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('member');

        app(PointCashOutService::class)->create($nasabah, 10);
    }

    public function test_rejects_insufficient_points(): void
    {
        $this->activeRule();
        $nasabah = $this->member(5);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak cukup');

        app(PointCashOutService::class)->create($nasabah, 10);
    }

    public function test_fails_without_active_rule(): void
    {
        $nasabah = $this->member(50);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('aturan poin aktif');

        app(PointCashOutService::class)->create($nasabah, 10);
    }

    public function test_fails_when_rule_has_zero_rate(): void
    {
        PointRule::factory()->create([
            'points_per_rupiah' => 0.001,
            'rupiah_per_point' => 0,
            'effective_from' => now()->subDay()->toDateString(),
        ]);
        $nasabah = $this->member(50);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rupiah per Poin');

        app(PointCashOutService::class)->create($nasabah, 10);
    }

    public function test_snapshot_preserved_after_rate_change(): void
    {
        $rule = $this->activeRule(1000);
        $nasabah = $this->member(50);

        $cashOut = app(PointCashOutService::class)->create($nasabah, 10);

        $this->assertSame('1000.00', (string) $cashOut->rate_snapshot);
        $this->assertSame('10000.00', (string) $cashOut->cash_amount);

        // Rate changes afterwards
        $rule->update(['rupiah_per_point' => 500]);

        $this->assertSame('1000.00', (string) $cashOut->fresh()->rate_snapshot);
        $this->assertSame('10000.00', (string) $cashOut->fresh()->cash_amount);
    }
}
