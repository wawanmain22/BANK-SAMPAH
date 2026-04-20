<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Balance;
use App\Models\BalanceHistory;
use App\Models\PointCashOut;
use App\Models\PointHistory;
use App\Models\PointRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Converts member points into saldo_tersedia (immediately withdrawable).
 *
 * Uses the active PointRule's rupiah_per_point as the conversion rate, snapshot
 * onto the cash-out record so history stays intact when rates change later.
 */
class PointCashOutService
{
    public function create(
        User $nasabah,
        int $pointsUsed,
        ?User $processedBy = null,
        ?string $notes = null,
    ): PointCashOut {
        if (! $nasabah->isNasabah()) {
            throw new InvalidArgumentException('User must be a nasabah.');
        }

        if (! $nasabah->is_member) {
            throw new InvalidArgumentException('Hanya member yang bisa menukar poin ke saldo.');
        }

        if ($pointsUsed <= 0) {
            throw new InvalidArgumentException('Poin yang ditukar harus lebih dari 0.');
        }

        $rule = PointRule::resolveActive();

        if (! $rule) {
            throw new InvalidArgumentException('Belum ada aturan poin aktif. Set di menu Master Poin.');
        }

        $rate = (float) $rule->rupiah_per_point;

        if ($rate <= 0) {
            throw new InvalidArgumentException('Aturan poin aktif belum punya konversi Rupiah per Poin. Update di Master Poin.');
        }

        return DB::transaction(function () use ($nasabah, $pointsUsed, $processedBy, $notes, $rule, $rate) {
            $balance = Balance::firstOrCreate(['user_id' => $nasabah->id]);

            if ((int) $balance->points < $pointsUsed) {
                throw new InvalidArgumentException('Poin nasabah tidak cukup.');
            }

            $cashAmount = round($pointsUsed * $rate, 2);

            $balance->points = (int) $balance->points - $pointsUsed;
            $balance->saldo_tersedia = (float) $balance->saldo_tersedia + $cashAmount;
            $balance->save();

            $cashOut = PointCashOut::create([
                'user_id' => $nasabah->id,
                'point_rule_id' => $rule->id,
                'points_used' => $pointsUsed,
                'rate_snapshot' => $rate,
                'cash_amount' => $cashAmount,
                'notes' => $notes,
                'processed_by' => $processedBy?->id,
                'cashed_out_at' => now(),
            ]);

            PointHistory::create([
                'user_id' => $nasabah->id,
                'point_rule_id' => $rule->id,
                'type' => 'redeem',
                'points' => -$pointsUsed,
                'balance_after' => $balance->points,
                'rate_snapshot' => $rate,
                'source_type' => PointCashOut::class,
                'source_id' => $cashOut->id,
                'description' => "Tukar {$pointsUsed} poin menjadi saldo Rp ".number_format($cashAmount, 0, ',', '.'),
                'created_by' => $processedBy?->id,
            ]);

            BalanceHistory::create([
                'user_id' => $nasabah->id,
                'bucket' => 'tersedia',
                'type' => 'point_cashout',
                'amount' => $cashAmount,
                'balance_after' => $balance->saldo_tersedia,
                'source_type' => PointCashOut::class,
                'source_id' => $cashOut->id,
                'description' => "Poin cashout #{$cashOut->id}",
                'created_by' => $processedBy?->id,
            ]);

            return $cashOut;
        });
    }
}
