<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Mail\EmailOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ForgotPasswordOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_reset_password_page_renders(): void
    {
        $this->get(route('password.reset', ['email' => 'user@example.com']))->assertOk();
    }

    public function test_request_sends_otp_for_existing_user(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'user@example.com']);

        Livewire::test('pages::auth.forgot-password')
            ->set('email', 'user@example.com')
            ->call('submit')
            ->assertRedirect(route('password.reset', ['email' => 'user@example.com']));

        Mail::assertSent(EmailOtpMail::class);

        $this->assertDatabaseHas('email_otps', [
            'email' => 'user@example.com',
            'purpose' => EmailOtp::PURPOSE_PASSWORD_RESET,
        ]);
    }

    public function test_request_does_not_leak_unknown_accounts(): void
    {
        Mail::fake();

        Livewire::test('pages::auth.forgot-password')
            ->set('email', 'ghost@example.com')
            ->call('submit')
            ->assertRedirect(route('password.reset', ['email' => 'ghost@example.com']));

        Mail::assertNothingSent();
    }

    public function test_reset_password_with_valid_otp_updates_password(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('old-password'),
        ]);

        // Seed a known OTP (bypass service to control plaintext).
        EmailOtp::create([
            'email' => 'user@example.com',
            'purpose' => EmailOtp::PURPOSE_PASSWORD_RESET,
            'code_hash' => Hash::make('111222'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        Livewire::test('pages::auth.reset-password', ['email' => 'user@example.com'])
            ->set('email', 'user@example.com')
            ->set('code', '111222')
            ->set('password', 'new-password-2024')
            ->set('password_confirmation', 'new-password-2024')
            ->call('submit')
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-2024', $user->refresh()->password));
    }

    public function test_reset_password_rejects_wrong_otp(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        EmailOtp::create([
            'email' => 'user@example.com',
            'purpose' => EmailOtp::PURPOSE_PASSWORD_RESET,
            'code_hash' => Hash::make('111222'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        Livewire::test('pages::auth.reset-password', ['email' => 'user@example.com'])
            ->set('email', 'user@example.com')
            ->set('code', '999000')
            ->set('password', 'new-password-2024')
            ->set('password_confirmation', 'new-password-2024')
            ->call('submit')
            ->assertHasErrors(['code']);
    }
}
