<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin Bank Sampah',
            'email' => 'admin@banksampah.test',
        ]);

        User::factory()->owner()->create([
            'name' => 'Pak Toni',
            'email' => 'owner@banksampah.test',
        ]);

        $this->call([
            WasteCategorySeeder::class,
            WasteItemSeeder::class,
            PointRuleSeeder::class,
            PartnerSeeder::class,
            ProductSeeder::class,
            ArticleSeeder::class,
            DemoTransactionSeeder::class,
        ]);
    }
}
