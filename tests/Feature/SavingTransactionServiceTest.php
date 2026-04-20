<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PointRule;
use App\Models\User;
use App\Models\WasteItem;
use App\Models\WastePrice;
use App\Services\SavingTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SavingTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function itemWithPrice(float $price = 3000): WasteItem
    {
        $item = WasteItem::factory()->create(['price_per_unit' => $price]);
        WastePrice::factory()->create([
            'waste_item_id' => $item->id,
            'price_per_unit' => $price,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        return $item->fresh('currentPrice');
    }

    private function activePointRule(float $rate = 0.001): PointRule
    {
        return PointRule::factory()->create([
            'points_per_rupiah' => $rate,
            'effective_from' => now()->subDay()->toDateString(),
        ]);
    }

    public function test_creates_transaction_items_and_updates_tertahan(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        $admin = User::factory()->admin()->create();
        $item = $this->itemWithPrice(3000);

        $transaction = app(SavingTransactionService::class)->create(
            $nasabah,
            [['waste_item_id' => $item->id, 'quantity' => 2.5]],
            notes: 'Test',
            createdBy: $admin,
        );

        $this->assertSame('7500.00', (string) $transaction->total_value);
        $this->assertSame('2.500', (string) $transaction->total_weight);
        $this->assertCount(1, $transaction->items);

        $txItem = $transaction->items->first();
        $this->assertSame('3000.00', (string) $txItem->price_per_unit_snapshot);
        $this->assertSame('7500.00', (string) $txItem->subtotal);
        $this->assertSame($item->code, $txItem->item_code_snapshot);
        $this->assertSame($item->name, $txItem->item_name_snapshot);

        $balance = $nasabah->balance()->first();
        $this->assertSame('7500.00', (string) $balance->saldo_tertahan);
        $this->assertSame('0.00', (string) $balance->saldo_tersedia);

        $this->assertDatabaseHas('balance_histories', [
            'user_id' => $nasabah->id,
            'bucket' => 'tertahan',
            'type' => 'nabung',
            'amount' => '7500.00',
            'balance_after' => '7500.00',
            'created_by' => $admin->id,
        ]);
    }

    public function test_member_earns_points_from_transaction_value(): void
    {
        $this->activePointRule(0.001);

        $nasabah = User::factory()->nasabah()->create([
            'is_member' => true,
            'member_joined_at' => now(),
        ]);
        $item = $this->itemWithPrice(5000);

        $transaction = app(SavingTransactionService::class)->create(
            $nasabah,
            [['waste_item_id' => $item->id, 'quantity' => 3]],
        );

        // 3 * 5000 = 15000. With points_per_rupiah=0.001 → 15 points
        $this->assertSame(15, $transaction->points_awarded);

        $this->assertSame(15, (int) $nasabah->balance()->first()->points);

        $this->assertDatabaseHas('point_histories', [
            'user_id' => $nasabah->id,
            'type' => 'earn',
            'points' => 15,
            'balance_after' => 15,
        ]);
    }

    public function test_member_gets_zero_points_when_no_active_rule(): void
    {
        $nasabah = User::factory()->nasabah()->create([
            'is_member' => true,
            'member_joined_at' => now(),
        ]);
        $item = $this->itemWithPrice(5000);

        $transaction = app(SavingTransactionService::class)->create(
            $nasabah,
            [['waste_item_id' => $item->id, 'quantity' => 3]],
        );

        $this->assertSame(0, $transaction->points_awarded);
    }

    public function test_non_member_gets_no_points(): void
    {
        $this->activePointRule();

        $nasabah = User::factory()->nasabah()->create(['is_member' => false]);
        $item = $this->itemWithPrice(5000);

        $transaction = app(SavingTransactionService::class)->create(
            $nasabah,
            [['waste_item_id' => $item->id, 'quantity' => 3]],
        );

        $this->assertSame(0, $transaction->points_awarded);
        $this->assertDatabaseMissing('point_histories', ['user_id' => $nasabah->id]);
    }

    public function test_cannot_save_for_non_nasabah(): void
    {
        $owner = User::factory()->owner()->create();
        $item = $this->itemWithPrice();

        $this->expectException(InvalidArgumentException::class);

        app(SavingTransactionService::class)->create(
            $owner,
            [['waste_item_id' => $item->id, 'quantity' => 1]],
        );
    }

    public function test_requires_at_least_one_item(): void
    {
        $nasabah = User::factory()->nasabah()->create();

        $this->expectException(InvalidArgumentException::class);

        app(SavingTransactionService::class)->create($nasabah, []);
    }

    public function test_fails_when_item_has_no_active_price(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        $item = WasteItem::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('belum memiliki harga aktif');

        app(SavingTransactionService::class)->create(
            $nasabah,
            [['waste_item_id' => $item->id, 'quantity' => 1]],
        );
    }

    public function test_snapshot_stays_intact_when_price_changes_later(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        $item = $this->itemWithPrice(3000);

        $transaction = app(SavingTransactionService::class)->create(
            $nasabah,
            [['waste_item_id' => $item->id, 'quantity' => 1]],
        );

        // Price changes later
        WastePrice::factory()->create([
            'waste_item_id' => $item->id,
            'price_per_unit' => 9999,
            'effective_from' => now()->addDay()->toDateString(),
        ]);

        $this->assertSame('3000.00', (string) $transaction->items->first()->price_per_unit_snapshot);
    }

    public function test_multiple_transactions_accumulate_tertahan(): void
    {
        $nasabah = User::factory()->nasabah()->create();
        $item = $this->itemWithPrice(1000);
        $service = app(SavingTransactionService::class);

        $service->create($nasabah, [['waste_item_id' => $item->id, 'quantity' => 2]]);
        $service->create($nasabah, [['waste_item_id' => $item->id, 'quantity' => 3]]);

        $this->assertSame('5000.00', (string) $nasabah->balance()->first()->saldo_tertahan);
    }
}
