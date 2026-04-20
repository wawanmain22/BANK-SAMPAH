<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $seedData = [
            [
                'name' => 'Paving Block',
                'description' => 'Paving block daur ulang plastik, ukuran 20x10 cm.',
                'unit' => 'pcs',
                'price' => 3500,
                'points_cost' => 5,
            ],
            [
                'name' => 'Pupuk Kompos',
                'description' => 'Kompos organik dari sampah rumah tangga, kemasan 1 kg.',
                'unit' => 'kg',
                'price' => 6000,
                'points_cost' => 8,
            ],
            [
                'name' => 'Tote Bag Upcycle',
                'description' => 'Tote bag dari banner bekas, custom motif.',
                'unit' => 'pcs',
                'price' => 35000,
                'points_cost' => 40,
            ],
            [
                'name' => 'Pot Tanaman Mini',
                'description' => 'Pot hias dari botol plastik bekas, 2 warna.',
                'unit' => 'pcs',
                'price' => 8000,
                'points_cost' => 10,
            ],
            [
                'name' => 'Kursi Palet',
                'description' => 'Kursi taman dari palet kayu bekas.',
                'unit' => 'pcs',
                'price' => 250000,
                'points_cost' => 300,
            ],
        ];

        $demoImage = '/images/demo/merchandise.jpg';

        foreach ($seedData as $data) {
            $product = Product::firstOrCreate(
                ['name' => $data['name']],
                [
                    ...$data,
                    'slug' => Str::slug($data['name']).'-'.Str::random(4),
                    'stock' => 0,
                    'is_active' => true,
                    'image' => $demoImage,
                ],
            );

            if (! $product->prices()->exists()) {
                ProductPrice::create([
                    'product_id' => $product->id,
                    'price_per_unit' => $data['price'],
                    'effective_from' => now()->toDateString(),
                    'notes' => 'Harga awal seed.',
                ]);
            }
        }
    }
}
