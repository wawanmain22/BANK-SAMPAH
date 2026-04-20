<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\SedekahTransaction;
use App\Models\User;
use App\Models\WasteItem;
use App\Services\InventoryService;
use App\Services\SedekahTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SedekahTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_sedekah_and_adds_to_sedekah_pool(): void
    {
        $admin = User::factory()->admin()->create();
        $item = WasteItem::factory()->create();

        $transaction = app(SedekahTransactionService::class)->create(
            [['waste_item_id' => $item->id, 'quantity' => 2.5]],
            donorName: 'Bapak Anonim',
            notes: 'Sedekah sampah kering',
            createdBy: $admin,
        );

        $this->assertSame('Bapak Anonim', $transaction->donor_name);
        $this->assertSame('2.500', (string) $transaction->total_weight);
        $this->assertCount(1, $transaction->items);

        $inv = Inventory::where('waste_item_id', $item->id)
            ->where('source', InventoryService::SOURCE_SEDEKAH)
            ->first();
        $this->assertSame('2.500', (string) $inv->stock);

        // Sedekah must NOT populate nabung pool.
        $this->assertNull(
            Inventory::where('waste_item_id', $item->id)
                ->where('source', InventoryService::SOURCE_NABUNG)
                ->first()
        );

        $this->assertDatabaseHas('inventory_movements', [
            'waste_item_id' => $item->id,
            'source' => 'sedekah',
            'direction' => 'in',
            'reason' => 'sedekah',
            'quantity' => '2.500',
        ]);
    }

    public function test_does_not_create_balance_or_points(): void
    {
        $nasabah = User::factory()->nasabah()->create(['is_member' => true, 'member_joined_at' => now()]);
        $item = WasteItem::factory()->create();

        app(SedekahTransactionService::class)->create(
            [['waste_item_id' => $item->id, 'quantity' => 10]],
            donor: $nasabah,
        );

        $this->assertNull($nasabah->balance()->first());
        $this->assertDatabaseMissing('point_histories', ['user_id' => $nasabah->id]);
        $this->assertDatabaseMissing('balance_histories', ['user_id' => $nasabah->id]);
    }

    public function test_donor_must_be_nasabah_if_provided(): void
    {
        $owner = User::factory()->owner()->create();
        $item = WasteItem::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(SedekahTransactionService::class)->create(
            [['waste_item_id' => $item->id, 'quantity' => 1]],
            donor: $owner,
        );
    }

    public function test_requires_at_least_one_item(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(SedekahTransactionService::class)->create([]);
    }

    public function test_anonymous_donor_is_allowed(): void
    {
        $item = WasteItem::factory()->create();

        $transaction = app(SedekahTransactionService::class)->create(
            [['waste_item_id' => $item->id, 'quantity' => 3]],
        );

        $this->assertInstanceOf(SedekahTransaction::class, $transaction);
        $this->assertNull($transaction->user_id);
        $this->assertNull($transaction->donor_name);
    }
}
