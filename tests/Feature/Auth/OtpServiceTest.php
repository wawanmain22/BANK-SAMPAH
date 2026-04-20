<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Mail\EmailOtpMail;
use App\Models\EmailOtp;
use App\Services\EmailOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_creates_otp_and_dispatches_mail(): void
    {
        Mail::fake();

        $otp = app(EmailOtpService::class)->send('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET);

        $this->assertNotNull($otp);
        $this->assertSame('user@example.com', $otp->email);
        $this->assertSame(EmailOtp::PURPOSE_PASSWORD_RESET, $otp->purpose);
        $this->assertNotNull($otp->expires_at);
        $this->assertNull($otp->used_at);

        Mail::assertSent(EmailOtpMail::class, function (EmailOtpMail $mail) {
            return $mail->hasTo('user@example.com') && $mail->purpose === EmailOtp::PURPOSE_PASSWORD_RESET;
        });
    }

    public function test_send_cooldown_prevents_rapid_reissue(): void
    {
        Mail::fake();
        $service = app(EmailOtpService::class);

        $service->send('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tunggu');

        $service->send('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET);
    }

    public function test_send_invalidates_previous_unused_otps(): void
    {
        Mail::fake();
        $service = app(EmailOtpService::class);

        $first = $service->send('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET);

        // Fast-forward past cooldown.
        $this->travel(EmailOtp::COOLDOWN_SECONDS + 1)->seconds();

        $second = $service->send('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET);

        $this->assertNotNull($first->fresh()->used_at);
        $this->assertNull($second->fresh()->used_at);
    }

    public function test_verify_accepts_correct_code_and_marks_used(): void
    {
        Mail::fake();
        $service = app(EmailOtpService::class);
        $service->send('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET);

        // Intercept the sent code via the most recent OTP hash check. We need the plaintext.
        // Use the testing shortcut: generate a known OTP by crafting it via Hash.
        $plain = $this->getGeneratedCode();
        $otp = EmailOtp::latest('id')->first();
        $otp->code_hash = \Illuminate\Support\Facades\Hash::make($plain);
        $otp->save();

        $verified = $service->verify('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET, $plain);

        $this->assertNotNull($verified->used_at);
    }

    public function test_verify_rejects_wrong_code_and_increments_attempts(): void
    {
        Mail::fake();
        $service = app(EmailOtpService::class);
        $service->send('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET);

        $this->expectException(InvalidArgumentException::class);

        try {
            $service->verify('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET, '000000');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(1, (int) EmailOtp::latest('id')->first()->attempts);

            throw $e;
        }
    }

    public function test_verify_rejects_expired_code(): void
    {
        Mail::fake();
        $service = app(EmailOtpService::class);
        $service->send('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET);

        $this->travel(EmailOtp::TTL_MINUTES + 1)->minutes();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('kadaluarsa');

        $service->verify('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET, '123456');
    }

    public function test_verify_locks_out_after_max_attempts(): void
    {
        Mail::fake();
        $service = app(EmailOtpService::class);
        $service->send('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET);

        $otp = EmailOtp::latest('id')->first();
        $otp->attempts = EmailOtp::MAX_ATTEMPTS;
        $otp->save();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('melebihi batas');

        $service->verify('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET, '123456');
    }

    public function test_verify_rejects_non_numeric_code(): void
    {
        $service = app(EmailOtpService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('6 digit');

        $service->verify('user@example.com', EmailOtp::PURPOSE_PASSWORD_RESET, 'abcdef');
    }

    public function test_send_rejects_unknown_purpose(): void
    {
        Mail::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Purpose OTP tidak valid');

        app(EmailOtpService::class)->send('user@example.com', 'hack');
    }

    private function getGeneratedCode(): string
    {
        return '123456';
    }
}
