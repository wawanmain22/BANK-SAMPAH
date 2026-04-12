<?php

namespace Database\Seeders;

use App\Models\WasteCategory;
use App\Models\WastePrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WasteCategorySeeder extends Seeder
{
    public function run(): void
    {
        $seedData = [
            ['name' => 'Plastik Botol PET', 'unit' => 'kg', 'price' => 3500],
            ['name' => 'Plastik Kresek', 'unit' => 'kg', 'price' => 1000],
            ['name' => 'Kardus', 'unit' => 'kg', 'price' => 2500],
            ['name' => 'Kertas Putih', 'unit' => 'kg', 'price' => 2000],
            ['name' => 'Kertas Koran', 'unit' => 'kg', 'price' => 1500],
            ['name' => 'Besi', 'unit' => 'kg', 'price' => 5000],
            ['name' => 'Aluminium', 'unit' => 'kg', 'price' => 12000],
            ['name' => 'Kaca', 'unit' => 'kg', 'price' => 500],
        ];

        foreach ($seedData as $item) {
            $category = WasteCategory::query()->firstOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'unit' => $item['unit'],
                    'is_active' => true,
                ],
            );

            if (! $category->prices()->exists()) {
                WastePrice::create([
                    'waste_category_id' => $category->id,
                    'price_per_unit' => $item['price'],
                    'effective_from' => now()->toDateString(),
                    'notes' => 'Harga awal seed.',
                ]);
            }
        }
    }
}
