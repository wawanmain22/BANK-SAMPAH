<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WasteCategory;
use App\Models\WasteItem;
use App\Models\WastePrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the waste item master from the on-site banner
 * ("Daftar Harga Sampah Terpilah Yang Diterima" — Bank Sampah Sukamaju Sejahtera).
 * Items marked PENDING on the banner are seeded as inactive with price 0.
 */
class WasteItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Kertas
            ['prefix' => 'KT', 'code' => 'KT1', 'name' => 'Dus', 'unit' => 'kg', 'price' => 1000],
            ['prefix' => 'KT', 'code' => 'KT2', 'name' => 'Duplex', 'unit' => 'kg', 'price' => 300],
            ['prefix' => 'KT', 'code' => 'KT3', 'name' => 'Arsip', 'unit' => 'kg', 'price' => 800],
            ['prefix' => 'KT', 'code' => 'KT4', 'name' => 'Buku', 'unit' => 'kg', 'price' => 500],

            // Logam
            ['prefix' => 'LG', 'code' => 'LG1', 'name' => 'Besi 1', 'unit' => 'kg', 'price' => 1500],
            ['prefix' => 'LG', 'code' => 'LG2', 'name' => 'Besi 2 (Paku)', 'unit' => 'kg', 'price' => 1000],
            ['prefix' => 'LG', 'code' => 'LG3', 'name' => 'Kaleng', 'unit' => 'kg', 'price' => 500],
            ['prefix' => 'LG', 'code' => 'LG4', 'name' => 'Kaleng Aluminium/Aro', 'unit' => 'kg', 'price' => 4500],
            ['prefix' => 'LG', 'code' => 'LG5', 'name' => 'Tembaga', 'unit' => 'kg', 'price' => 3000],
            ['prefix' => 'LG', 'code' => 'LG6', 'name' => 'Seng', 'unit' => 'kg', 'price' => 500],

            // Botol
            ['prefix' => 'BL', 'code' => 'BL1', 'name' => 'Botol Bening', 'unit' => 'pcs', 'price' => 100],
            ['prefix' => 'BL', 'code' => 'BL2', 'name' => 'Botol Warna/Kecap', 'unit' => 'pcs', 'price' => 200],
            ['prefix' => 'BL', 'code' => 'BL3', 'name' => 'Beling', 'unit' => 'kg', 'price' => 300],

            // Plastik
            ['prefix' => 'PL', 'code' => 'PL1', 'name' => 'AGB', 'unit' => 'kg', 'price' => 2700],
            ['prefix' => 'PL', 'code' => 'PL2', 'name' => 'AGK', 'unit' => 'kg', 'price' => 1500],
            ['prefix' => 'PL', 'code' => 'PL3', 'name' => 'PET Botol Bersih', 'unit' => 'kg', 'price' => 2000],
            ['prefix' => 'PL', 'code' => 'PL4', 'name' => 'PET Botol Kotor', 'unit' => 'kg', 'price' => 1500],
            ['prefix' => 'PL', 'code' => 'PL5', 'name' => 'Ale-Ale', 'unit' => 'kg', 'price' => 1000],
            ['prefix' => 'PL', 'code' => 'PL6', 'name' => 'Mizone Bersih', 'unit' => 'kg', 'price' => 500],
            ['prefix' => 'PL', 'code' => 'PL7', 'name' => 'Mizone Kotor', 'unit' => 'kg', 'price' => 300],
            ['prefix' => 'PL', 'code' => 'PL8', 'name' => 'Jeli', 'unit' => 'kg', 'price' => 2000, 'notes' => 'Harga banner Rp 1.000 - 3.000. Default tengah.'],
            ['prefix' => 'PL', 'code' => 'PL9', 'name' => 'Kerasan', 'unit' => 'kg', 'price' => 300],
            ['prefix' => 'PL', 'code' => 'PL10', 'name' => 'Gebrus 1 (GB 1)', 'unit' => 'kg', 'price' => 1200],
            ['prefix' => 'PL', 'code' => 'PL11', 'name' => 'Gebrus 2 (GB 2)', 'unit' => 'kg', 'price' => 1000],
            ['prefix' => 'PL', 'code' => 'PL11A', 'name' => 'Gebrus 3 (GB 3)', 'unit' => 'kg', 'price' => 500],
            ['prefix' => 'PL', 'code' => 'PL12', 'name' => 'LD (Tutup Galon Merk Aqua)', 'unit' => 'kg', 'price' => 2000],
            ['prefix' => 'PL', 'code' => 'PL13', 'name' => 'Kristal', 'unit' => 'kg', 'price' => 2000],
            ['prefix' => 'PL', 'code' => 'PL14', 'name' => 'Blowing', 'unit' => 'kg', 'price' => 1500],
            ['prefix' => 'PL', 'code' => 'PL15', 'name' => 'Galon Pecah Merk Aqua', 'unit' => 'kg', 'price' => 2500],

            // Lain-lain
            ['prefix' => 'L', 'code' => 'L1', 'name' => 'Karpet Talang/Jas Hujan', 'unit' => 'kg', 'price' => 500],
            ['prefix' => 'L', 'code' => 'L2', 'name' => 'Sendal Karet', 'unit' => 'kg', 'price' => 0, 'pending' => true],
            ['prefix' => 'L', 'code' => 'L3', 'name' => 'Selang Air', 'unit' => 'kg', 'price' => 0, 'pending' => true],
            ['prefix' => 'L', 'code' => 'L4', 'name' => 'Cangkang Kabel', 'unit' => 'kg', 'price' => 0, 'pending' => true],
            ['prefix' => 'L', 'code' => 'L5', 'name' => 'Regulator Bekas', 'unit' => 'kg', 'price' => 3000],
            ['prefix' => 'L', 'code' => 'L6', 'name' => 'Paralon', 'unit' => 'kg', 'price' => 600],
            ['prefix' => 'L', 'code' => 'L7', 'name' => 'Fiber', 'unit' => 'kg', 'price' => 350],
            ['prefix' => 'L', 'code' => 'L8', 'name' => 'Minyak Jelantah', 'unit' => 'kg', 'price' => 4000],
            ['prefix' => 'L', 'code' => 'L9', 'name' => 'Tray Telor', 'unit' => 'kg', 'price' => 500],

            // Residu
            ['prefix' => 'R', 'code' => 'R1', 'name' => 'Plastik Residu Multilayer', 'unit' => 'kg', 'price' => 500],
        ];

        $categoriesByPrefix = WasteCategory::query()->get()->keyBy('code_prefix');

        foreach ($items as $row) {
            $category = $categoriesByPrefix->get($row['prefix']);

            if (! $category) {
                continue;
            }

            $isPending = $row['pending'] ?? false;

            $item = WasteItem::query()->firstOrCreate(
                ['code' => $row['code']],
                [
                    'waste_category_id' => $category->id,
                    'name' => $row['name'],
                    'slug' => Str::slug($row['name'].'-'.$row['code']),
                    'unit' => $row['unit'],
                    'price_per_unit' => $row['price'],
                    'description' => $row['notes'] ?? ($isPending ? 'Harga menunggu update (PENDING pada banner).' : null),
                    'is_active' => ! $isPending,
                ],
            );

            if ($row['price'] > 0 && ! $item->prices()->exists()) {
                WastePrice::create([
                    'waste_item_id' => $item->id,
                    'price_per_unit' => $row['price'],
                    'effective_from' => now()->toDateString(),
                    'notes' => 'Harga awal seed (banner Bank Sampah).',
                ]);
            }
        }
    }
}
