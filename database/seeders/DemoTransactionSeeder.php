<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use App\Models\WasteCategory;
use App\Services\BalanceService;
use App\Services\ProcessingTransactionService;
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

        $categories = WasteCategory::active()->with('currentPrice')->get();
        $partners = Partner::active()->get();
        $products = Product::active()->get();

        if ($categories->isEmpty()) {
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
                $picked = $categories->random(fake()->numberBetween(1, 3));
                $items = $picked->map(fn ($c) => [
                    'waste_category_id' => $c->id,
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

        $sedekah->create(
            items: [
                ['waste_category_id' => $categories->random()->id, 'quantity' => 3.5],
                ['waste_category_id' => $categories->random()->id, 'quantity' => 2],
            ],
            donorName: 'Bu Hartini (RT 05)',
            notes: 'Sedekah rutin warga',
            createdBy: $admin,
        );

        $sedekah->create(
            items: [['waste_category_id' => $categories->random()->id, 'quantity' => 5]],
            donor: $nasabahCreated->random(),
            notes: 'Donasi dari nasabah',
            createdBy: $admin,
        );

        if ($partners->isNotEmpty()) {
            foreach ($partners->take(2) as $partner) {
                $availableCats = WasteCategory::with(['inventory', 'currentPrice'])
                    ->whereHas('inventory', fn ($q) => $q->where('stock', '>', 5))
                    ->get();

                if ($availableCats->isEmpty()) {
                    continue;
                }

                $items = $availableCats->random(min(2, $availableCats->count()))->map(function ($c) {
                    $stock = (float) ($c->inventory?->stock ?? 0);

                    return [
                        'waste_category_id' => $c->id,
                        'quantity' => round($stock * 0.3, 3),
                        'price_per_unit' => (float) ($c->currentPrice?->price_per_unit ?? 1000) * 1.1,
                    ];
                })->filter(fn ($i) => $i['quantity'] > 0)->values()->toArray();

                if (! empty($items)) {
                    $sales->create($partner, $items, notes: 'Pengiriman rutin', createdBy: $admin);
                }
            }
        }

        if ($products->isNotEmpty()) {
            $availableCats = WasteCategory::with('inventory')
                ->whereHas('inventory', fn ($q) => $q->where('stock', '>', 2))
                ->get();

            if ($availableCats->isNotEmpty()) {
                $processing->create(
                    inputs: $availableCats->random(min(2, $availableCats->count()))->map(fn ($c) => [
                        'waste_category_id' => $c->id,
                        'quantity' => min((float) $c->inventory->stock * 0.4, 3),
                    ])->toArray(),
                    outputs: [
                        ['product_id' => $products->first()->id, 'quantity' => 20],
                        ['product_id' => $products->get(1)?->id ?? $products->first()->id, 'quantity' => 5],
                    ],
                    notes: 'Pengolahan batch April',
                    createdBy: $admin,
                );
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
