<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\BalanceHistory;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BalanceService
{
    /**
     * Move funds from saldo_tertahan to saldo_tersedia when admin confirms
     * the mitra has paid out the corresponding waste batch.
     */
    public function release(User $nasabah, float $amount, User $admin, ?string $notes = null): Balance
    {
        if (! $nasabah->isNasabah()) {
            throw new InvalidArgumentException('User must be a nasabah.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Jumlah harus lebih dari 0.');
        }

        return DB::transaction(function () use ($nasabah, $amount, $admin, $notes) {
            $balance = Balance::firstOrCreate(['user_id' => $nasabah->id]);

            if ((float) $balance->saldo_tertahan < $amount) {
                throw new InvalidArgumentException('Saldo tertahan tidak cukup.');
            }

            $balance->saldo_tertahan = (float) $balance->saldo_tertahan - $amount;
            $balance->saldo_tersedia = (float) $balance->saldo_tersedia + $amount;
            $balance->save();

            BalanceHistory::create([
                'user_id' => $nasabah->id,
                'bucket' => 'tertahan',
                'type' => 'release',
                'amount' => -$amount,
                'balance_after' => $balance->saldo_tertahan,
                'description' => $notes ?: 'Release saldo ke tersedia',
                'created_by' => $admin->id,
            ]);

            BalanceHistory::create([
                'user_id' => $nasabah->id,
                'bucket' => 'tersedia',
                'type' => 'release',
                'amount' => $amount,
                'balance_after' => $balance->saldo_tersedia,
                'description' => $notes ?: 'Release saldo dari tertahan',
                'created_by' => $admin->id,
            ]);

            return $balance;
        });
    }

    /**
     * Process a withdrawal from saldo_tersedia (cash or transfer).
     *
     * @param  array<string, mixed>  $meta  bank_name, account_number, account_name
     */
    public function withdraw(
        User $nasabah,
        float $amount,
        string $method,
        User $admin,
        array $meta = [],
        ?string $notes = null,
    ): WithdrawalRequest {
        if (! $nasabah->isNasabah()) {
            throw new InvalidArgumentException('User must be a nasabah.');
        }

        if (! in_array($method, ['cash', 'transfer'], strict: true)) {
            throw new InvalidArgumentException('Metode pencairan tidak valid.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Jumlah harus lebih dari 0.');
        }

        return DB::transaction(function () use ($nasabah, $amount, $method, $admin, $meta, $notes) {
            $balance = Balance::firstOrCreate(['user_id' => $nasabah->id]);

            if ((float) $balance->saldo_tersedia < $amount) {
                throw new InvalidArgumentException('Saldo tersedia tidak cukup.');
            }

            $balance->saldo_tersedia = (float) $balance->saldo_tersedia - $amount;
            $balance->save();

            $withdrawal = WithdrawalRequest::create([
                'user_id' => $nasabah->id,
                'amount' => $amount,
                'method' => $method,
                'bank_name' => $meta['bank_name'] ?? null,
                'account_number' => $meta['account_number'] ?? null,
                'account_name' => $meta['account_name'] ?? null,
                'notes' => $notes,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            BalanceHistory::create([
                'user_id' => $nasabah->id,
                'bucket' => 'tersedia',
                'type' => 'withdrawal',
                'amount' => -$amount,
                'balance_after' => $balance->saldo_tersedia,
                'source_type' => WithdrawalRequest::class,
                'source_id' => $withdrawal->id,
                'description' => 'Pencairan #'.$withdrawal->id.' via '.$method,
                'created_by' => $admin->id,
            ]);

            return $withdrawal;
        });
    }
}
