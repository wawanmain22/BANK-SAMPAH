<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $seedData = [
            [
                'name' => 'CV Daur Ulang Sejahtera',
                'type' => 'pengepul',
                'phone' => '022-123456',
                'email' => 'info@daurulang.test',
                'address' => 'Jl. Industri No. 5, Bandung',
                'notes' => 'Mitra utama plastik & kertas.',
            ],
            [
                'name' => 'PT Logam Mulia',
                'type' => 'pabrik',
                'phone' => '022-234567',
                'email' => 'contact@logammulia.test',
                'address' => 'Kawasan Industri Cikarang',
                'notes' => 'Untuk besi dan aluminium.',
            ],
            [
                'name' => 'UD Kaca Jaya',
                'type' => 'pengepul',
                'phone' => '022-345678',
                'email' => null,
                'address' => 'Pasar Baru Bandung',
                'notes' => 'Penerima kaca pecah.',
            ],
        ];

        foreach ($seedData as $data) {
            Partner::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
