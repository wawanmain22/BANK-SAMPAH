<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\Product;
use App\Models\Redemption;
use App\Models\User;
use App\Services\RedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RedemptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_redeem_points(): void
    {
        $member = User::factory()->nasabah()->create([
            'is_member' => true,
            'member_joined_at' => now(),
        ]);
        Balance::create(['user_id' => $member->id, 'points' => 100]);

        $product = Product::factory()->create(['stock' => 5]);
        $admin = User::factory()->admin()->create();

        $redemption = app(RedemptionService::class)->create(
            nasabah: $member,
            product: $product,
            quantity: 2,
            pointsUsed: 50,
            processedBy: $admin,
        );

        $this->assertInstanceOf(Redemption::class, $redemption);
        $this->assertSame(50, (int) $member->balance()->first()->points);
        $this->assertSame('3.000', (string) $product->refresh()->stock);

        $this->assertDatabaseHas('point_histories', [
            'user_id' => $member->id,
            'type' => 'redeem',
            'points' => -50,
            'balance_after' => 50,
            'source_type' => Redemption::class,
        ]);
    }

    public function test_non_member_cannot_redeem(): void
    {
        $nasabah = User::factory()->nasabah()->create(['is_member' => false]);
        Balance::create(['user_id' => $nasabah->id, 'points' => 100]);
        $product = Product::factory()->create(['stock' => 5]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('member');

        app(RedemptionService::class)->create($nasabah, $product, 1, 10);
    }

    public function test_cannot_redeem_more_points_than_available(): void
    {
        $member = User::factory()->nasabah()->create(['is_member' => true, 'member_joined_at' => now()]);
        Balance::create(['user_id' => $member->id, 'points' => 10]);
        $product = Product::factory()->create(['stock' => 5]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Poin');

        app(RedemptionService::class)->create($member, $product, 1, 50);
    }

    public function test_cannot_redeem_when_stock_insufficient(): void
    {
        $member = User::factory()->nasabah()->create(['is_member' => true, 'member_joined_at' => now()]);
        Balance::create(['user_id' => $member->id, 'points' => 100]);
        $product = Product::factory()->create(['stock' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stok');

        app(RedemptionService::class)->create($member, $product, 3, 50);
    }
}
