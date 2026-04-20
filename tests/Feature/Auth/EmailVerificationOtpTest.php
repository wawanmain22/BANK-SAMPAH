<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class EmailVerificationOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_page_requires_auth(): void
    {
        $this->get(route('verification.notice'))->assertRedirect(route('login'));
    }

    public function test_verify_page_renders_for_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.notice'))->assertOk();
    }

    public function test_already_verified_user_is_redirected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertRedirect();
    }

    public function test_valid_otp_marks_email_verified(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'new@example.com']);

        EmailOtp::create([
            'email' => 'new@example.com',
            'purpose' => EmailOtp::PURPOSE_EMAIL_VERIFICATION,
            'code_hash' => Hash::make('654321'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test('pages::auth.verify-email')
            ->set('code', '654321')
            ->call('submit')
            ->assertRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_wrong_otp_rejected(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'new@example.com']);

        EmailOtp::create([
            'email' => 'new@example.com',
            'purpose' => EmailOtp::PURPOSE_EMAIL_VERIFICATION,
            'code_hash' => Hash::make('654321'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test('pages::auth.verify-email')
            ->set('code', '000000')
            ->call('submit')
            ->assertHasErrors(['code']);

        $this->assertNull($user->refresh()->email_verified_at);
    }

    public function test_registration_triggers_otp_email(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $this->post(route('register.store'), [
            'name' => 'Pendaftar Baru',
            'email' => 'fresh@example.com',
            'password' => 'password-2024',
            'password_confirmation' => 'password-2024',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'fresh@example.com']);
        $this->assertDatabaseHas('email_otps', [
            'email' => 'fresh@example.com',
            'purpose' => EmailOtp::PURPOSE_EMAIL_VERIFICATION,
        ]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\EmailOtpMail::class);
    }
}
