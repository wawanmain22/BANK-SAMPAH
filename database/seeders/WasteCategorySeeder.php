<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WasteCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WasteCategorySeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Kertas', 'code_prefix' => 'KT', 'description' => 'Dus, duplex, arsip, buku bekas.'],
            ['name' => 'Logam', 'code_prefix' => 'LG', 'description' => 'Besi, kaleng, tembaga, seng, aluminium.'],
            ['name' => 'Botol', 'code_prefix' => 'BL', 'description' => 'Botol kaca bening, botol warna, beling.'],
            ['name' => 'Plastik', 'code_prefix' => 'PL', 'description' => 'PET, kresek, galon, jenis plastik lain.'],
            ['name' => 'Lain-lain', 'code_prefix' => 'L', 'description' => 'Karpet, sandal karet, minyak jelantah, fiber, paralon.'],
            ['name' => 'Residu', 'code_prefix' => 'R', 'description' => 'Sampah multilayer tak terdaur.'],
        ];

        foreach ($groups as $group) {
            WasteCategory::query()->firstOrCreate(
                ['code_prefix' => $group['code_prefix']],
                [
                    'name' => $group['name'],
                    'slug' => Str::slug($group['name']),
                    'description' => $group['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
