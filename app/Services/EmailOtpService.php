<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\EmailOtpMail;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Manages email-based OTP codes for password reset and email verification.
 *
 * OTP codes are 6-digit numeric, hashed in DB, TTL 10 minutes, 5 attempt cap.
 * A 60-second cooldown between issues throttles abuse.
 */
class EmailOtpService
{
    /**
     * Generate and email a new OTP for the given email + purpose.
     * Invalidates any existing unused OTPs for the same pair.
     */
    public function send(string $email, string $purpose): EmailOtp
    {
        $this->assertPurpose($purpose);

        $email = strtolower(trim($email));

        $recent = EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('created_at', '>=', now()->subSeconds(EmailOtp::COOLDOWN_SECONDS))
            ->latest('id')
            ->first();

        if ($recent) {
            $wait = max(1, EmailOtp::COOLDOWN_SECONDS - now()->diffInSeconds($recent->created_at));

            throw new RuntimeException("Tunggu {$wait} detik sebelum meminta kode baru.");
        }

        // Invalidate older unused OTPs for same (email, purpose).
        EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = $this->generateCode();

        $otp = EmailOtp::create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(EmailOtp::TTL_MINUTES),
            'created_at' => now(),
        ]);

        Mail::to($email)->send(new EmailOtpMail($code, $purpose));

        return $otp;
    }

    /**
     * Verify an OTP. On success, marks it used. On failure, increments attempts.
     * Throws InvalidArgumentException with a user-friendly message on failure.
     */
    public function verify(string $email, string $purpose, string $code): EmailOtp
    {
        $this->assertPurpose($purpose);

        $email = strtolower(trim($email));
        $code = trim($code);

        if (! preg_match('/^\d{6}$/', $code)) {
            throw new InvalidArgumentException('Kode harus 6 digit angka.');
        }

        $otp = EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            throw new InvalidArgumentException('Kode tidak ditemukan. Minta kode baru.');
        }

        if ($otp->expires_at->isPast()) {
            throw new InvalidArgumentException('Kode sudah kadaluarsa. Minta kode baru.');
        }

        if ($otp->attempts >= EmailOtp::MAX_ATTEMPTS) {
            throw new InvalidArgumentException('Percobaan melebihi batas. Minta kode baru.');
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            $remaining = EmailOtp::MAX_ATTEMPTS - $otp->attempts;

            throw new InvalidArgumentException("Kode salah. Sisa percobaan: {$remaining}.");
        }

        $otp->used_at = now();
        $otp->save();

        return $otp;
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function assertPurpose(string $purpose): void
    {
        if (! in_array($purpose, [
            EmailOtp::PURPOSE_PASSWORD_RESET,
            EmailOtp::PURPOSE_EMAIL_VERIFICATION,
        ], true)) {
            throw new InvalidArgumentException("Purpose OTP tidak valid: '{$purpose}'.");
        }
    }
}
