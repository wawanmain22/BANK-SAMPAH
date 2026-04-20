<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PointRule;
use Illuminate\Database\Seeder;

class PointRuleSeeder extends Seeder
{
    public function run(): void
    {
        if (PointRule::query()->exists()) {
            return;
        }

        PointRule::create([
            'points_per_rupiah' => 0.001,     // nabung → poin: Rp 1.000 = 1 poin
            'rupiah_per_point' => 1000,       // poin → saldo: 1 poin = Rp 1.000
            'effective_from' => now()->toDateString(),
            'notes' => 'Default: 1 poin per Rp 1.000 (nabung), tukar balik 1 poin = Rp 1.000.',
            'is_active' => true,
        ]);
    }
}
