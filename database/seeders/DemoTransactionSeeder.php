<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Inventory;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use App\Models\WasteItem;
use App\Services\BalanceService;
use App\Services\InventoryService;
use App\Services\ProcessingTransactionService;
use App\Services\ProductSalesService;
use App\Services\RedemptionService;
use App\Services\SalesTransactionService;
use App\Services\SavingTransactionService;
use App\Services\SedekahTransactionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@banksampah.test')->first();

        if (! $admin) {
            return;
        }

        $wasteItems = WasteItem::active()->with(['category', 'currentPrice'])
            ->whereHas('currentPrice')
            ->get();
        $partners = Partner::active()->get();
        $products = Product::active()->get();

        if ($wasteItems->isEmpty()) {
            return;
        }

        $saving = app(SavingTransactionService::class);
        $balance = app(BalanceService::class);
        $sedekah = app(SedekahTransactionService::class);
        $sales = app(SalesTransactionService::class);
        $processing = app(ProcessingTransactionService::class);
        $redemption = app(RedemptionService::class);

        $nasabahDefs = [
            ['name' => 'Siti Aminah', 'email' => 'nasabah@banksampah.test', 'member' => true, 'txCount' => 4],
            ['name' => 'Budi Santoso', 'email' => 'budi@banksampah.test', 'member' => true, 'txCount' => 3],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@banksampah.test', 'member' => false, 'txCount' => 2],
            ['name' => 'Eko Nugroho', 'email' => 'eko@banksampah.test', 'member' => true, 'txCount' => 1],
            ['name' => 'Fatimah Hassan', 'email' => 'fatimah@banksampah.test', 'member' => false, 'txCount' => 2],
        ];

        $nasabahCreated = collect();

        foreach ($nasabahDefs as $def) {
            $nasabah = User::firstOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'password' => Hash::make('password'),
                    'role' => UserRole::Nasabah,
                    'phone' => '08'.fake()->numerify('##########'),
                    'address' => fake()->address(),
                    'is_member' => $def['member'],
                    'member_joined_at' => $def['member'] ? now()->subMonths(fake()->numberBetween(1, 6)) : null,
                    'email_verified_at' => now(),
                ],
            );

            if ($nasabah->savingTransactions()->exists()) {
                $nasabahCreated->push($nasabah);

                continue;
            }

            for ($i = $def['txCount']; $i >= 1; $i--) {
                $picked = $wasteItems->random(fake()->numberBetween(1, 3));
                $items = $picked->map(fn ($wi) => [
                    'waste_item_id' => $wi->id,
                    'quantity' => fake()->randomFloat(3, 0.5, 6),
                ])->toArray();

                $tx = $saving->create(
                    $nasabah,
                    $items,
                    notes: fake()->optional()->sentence(6),
                    createdBy: $admin,
                );

                $tx->update(['transacted_at' => now()->subDays($i * 5 + fake()->numberBetween(0, 3))]);
            }

            $nasabahCreated->push($nasabah);
        }

        foreach ($nasabahCreated as $nasabah) {
            $bal = $nasabah->balance()->first();
            if (! $bal || (float) $bal->saldo_tertahan <= 0) {
                continue;
            }

            $releaseAmount = round((float) $bal->saldo_tertahan * 0.6, 2);
            if ($releaseAmount > 0) {
                $balance->release($nasabah, $releaseAmount, $admin, 'Dana mitra sudah cair');
            }

            if (fake()->boolean(50)) {
                $bal = $nasabah->balance()->first();
                $withdrawAmount = round((float) $bal->saldo_tersedia * 0.5, 2);
                if ($withdrawAmount > 1000) {
                    $balance->withdraw(
                        $nasabah,
                        $withdrawAmount,
                        fake()->randomElement(['cash', 'transfer']),
                        $admin,
                        meta: [
                            'bank_name' => 'BCA',
                            'account_number' => fake()->numerify('##########'),
                            'account_name' => $nasabah->name,
                        ],
                        notes: 'Pencairan reguler',
                    );
                }
            }
        }

        // Sedekah seeds — pick a varied mix so the sedekah pool has enough
        // stock to support the processing demo below.
        $sedekahPicks = $wasteItems->random(min(4, $wasteItems->count()));

        foreach ($sedekahPicks as $wi) {
            $sedekah->create(
                items: [['waste_item_id' => $wi->id, 'quantity' => fake()->randomFloat(3, 3, 8)]],
                donorName: fake()->randomElement(['Bu Hartini (RT 05)', 'Pak Joko', 'Warga RT 03']),
                notes: 'Sedekah rutin warga',
                createdBy: $admin,
            );
        }

        if ($nasabahCreated->isNotEmpty()) {
            $sedekah->create(
                items: [['waste_item_id' => $wasteItems->random()->id, 'quantity' => 5]],
                donor: $nasabahCreated->random(),
                notes: 'Donasi dari nasabah',
                createdBy: $admin,
            );
        }

        // Sales — only consumes the nabung pool.
        if ($partners->isNotEmpty()) {
            foreach ($partners->take(2) as $partner) {
                $availableItems = WasteItem::with(['category', 'currentPrice'])
                    ->whereIn('id', Inventory::query()
                        ->where('source', InventoryService::SOURCE_NABUNG)
                        ->where('stock', '>', 5)
                        ->pluck('waste_item_id'))
                    ->get();

                if ($availableItems->isEmpty()) {
                    continue;
                }

                $items = $availableItems->random(min(2, $availableItems->count()))->map(function (WasteItem $wi) {
                    $stock = (float) (Inventory::query()
                        ->where('waste_item_id', $wi->id)
                        ->where('source', InventoryService::SOURCE_NABUNG)
                        ->value('stock') ?? 0);

                    return [
                        'waste_item_id' => $wi->id,
                        'quantity' => round($stock * 0.3, 3),
                        'price_per_unit' => (float) ($wi->currentPrice?->price_per_unit ?? 1000) * 1.1,
                    ];
                })->filter(fn ($i) => $i['quantity'] > 0)->values()->toArray();

                if (! empty($items)) {
                    $sales->create($partner, $items, notes: 'Pengiriman rutin', createdBy: $admin);
                }
            }
        }

        // Processing — only consumes the sedekah pool.
        if ($products->isNotEmpty()) {
            $availableItems = WasteItem::with('category')
                ->whereIn('id', Inventory::query()
                    ->where('source', InventoryService::SOURCE_SEDEKAH)
                    ->where('stock', '>', 2)
                    ->pluck('waste_item_id'))
                ->get();

            if ($availableItems->isNotEmpty()) {
                $processing->create(
                    inputs: $availableItems->random(min(2, $availableItems->count()))->map(function (WasteItem $wi) {
                        $stock = (float) (Inventory::query()
                            ->where('waste_item_id', $wi->id)
                            ->where('source', InventoryService::SOURCE_SEDEKAH)
                            ->value('stock') ?? 0);

                        return [
                            'waste_item_id' => $wi->id,
                            'quantity' => round(min($stock * 0.4, 3), 3),
                        ];
                    })->filter(fn ($i) => $i['quantity'] > 0)->values()->toArray(),
                    outputs: [
                        ['product_id' => $products->first()->id, 'quantity' => 20],
                        ['product_id' => $products->get(1)?->id ?? $products->first()->id, 'quantity' => 5],
                    ],
                    notes: 'Pengolahan batch April',
                    createdBy: $admin,
                );
            }
        }

        // Product sales — walk-in + nasabah mix, paid + pending, mixed payment methods.
        $productsWithStock = Product::where('stock', '>', 0)->get();

        if ($productsWithStock->isNotEmpty()) {
            $productSales = app(ProductSalesService::class);

            $demoSales = [
                [
                    'name' => 'Ibu Warti',
                    'phone' => '081234567801',
                    'method' => 'cash',
                    'status' => 'paid',
                    'buyer' => null,
                ],
                [
                    'name' => 'Pak Joko',
                    'phone' => '081234567802',
                    'method' => 'transfer',
                    'status' => 'paid',
                    'buyer' => null,
                ],
                [
                    'name' => 'Reseller Tika',
                    'phone' => '081234567803',
                    'method' => 'qris',
                    'status' => 'pending',
                    'buyer' => null,
                ],
                [
                    'name' => $nasabahCreated->first()?->name ?? 'Nasabah Demo',
                    'phone' => $nasabahCreated->first()?->phone ?? '081234567899',
                    'method' => 'cash',
                    'status' => 'paid',
                    'buyer' => $nasabahCreated->first(),
                ],
            ];

            foreach ($demoSales as $saleDef) {
                $picks = $productsWithStock->random(min(2, $productsWithStock->count()));

                $items = $picks
                    ->map(fn (Product $p) => [
                        'product_id' => $p->id,
                        'quantity' => max(1, min((int) floor((float) $p->stock * 0.1), 3)),
                    ])
                    ->filter(fn ($i) => $i['quantity'] > 0)
                    ->values()
                    ->toArray();

                if (empty($items)) {
                    continue;
                }

                try {
                    $productSales->create(
                        buyerName: $saleDef['name'],
                        buyerPhone: $saleDef['phone'],
                        items: $items,
                        buyerUser: $saleDef['buyer'],
                        paymentMethod: $saleDef['method'],
                        paymentStatus: $saleDef['status'],
                        notes: 'Demo penjualan produk.',
                        createdBy: $admin,
                    );
                } catch (\InvalidArgumentException) {
                    // Skip sale if stock depleted by earlier iteration.
                }

                $productsWithStock = Product::where('stock', '>', 0)->get();
            }
        }

        $memberWithPoints = $nasabahCreated
            ->map(fn ($n) => $n->fresh('balance'))
            ->first(fn ($n) => $n->is_member && (int) ($n->balance?->points ?? 0) > 10);

        if ($memberWithPoints) {
            $productWithStock = Product::where('stock', '>=', 1)->first();

            if ($productWithStock) {
                $redemption->create(
                    nasabah: $memberWithPoints,
                    product: $productWithStock,
                    quantity: 1,
                    pointsUsed: min(10, (int) $memberWithPoints->balance->points),
                    processedBy: $admin,
                    notes: 'Demo tukar poin',
                );
            }
        }
    }
}
