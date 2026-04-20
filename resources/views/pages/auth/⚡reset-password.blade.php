<?php

use App\Concerns\PasswordValidationRules;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\EmailOtpService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts.auth')] #[Title('Reset password')] class extends Component {
    use PasswordValidationRules, Toast;

    #[Url(as: 'email')]
    public string $email = '';

    public string $code = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
            'password' => $this->passwordRules(),
        ];
    }

    public function submit(EmailOtpService $service): void
    {
        $this->validate();

        $email = strtolower(trim($this->email));

        try {
            $service->verify($email, EmailOtp::PURPOSE_PASSWORD_RESET, $this->code);
        } catch (\InvalidArgumentException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->addError('email', __('Akun tidak ditemukan.'));

            return;
        }

        $user->password = $this->password;
        $user->setRememberToken(\Illuminate\Support\Str::random(60));
        $user->save();

        session()->flash('status', __('Password berhasil diubah. Silakan masuk dengan password baru.'));

        $this->redirect(route('login'), navigate: true);
    }

    public function resend(EmailOtpService $service): void
    {
        $email = strtolower(trim($this->email));

        if (! $email || ! User::where('email', $email)->exists()) {
            $this->info(__('Email tidak valid.'));

            return;
        }

        try {
            $service->send($email, EmailOtp::PURPOSE_PASSWORD_RESET);
            $this->success(__('Kode OTP baru telah dikirim ke email Anda.'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Reset password')"
        :description="__('Masukkan kode OTP yang dikirim ke email Anda dan buat password baru.')"
    />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="submit" class="flex flex-col gap-5">
        <x-mary-input
            wire:model="email"
            label="{{ __('Email') }}"
            type="email"
            icon="o-envelope"
            required
            readonly
            autocomplete="email"
            :error="$errors->first('email')"
        />

        <x-mary-input
            wire:model="code"
            label="{{ __('Kode OTP (6 digit)') }}"
            icon="o-key"
            maxlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            placeholder="123456"
            required
            autofocus
            :error="$errors->first('code')"
        />

        <x-mary-input
            wire:model="password"
            label="{{ __('Password baru') }}"
            type="password"
            icon="o-lock-closed"
            required
            autocomplete="new-password"
            :error="$errors->first('password')"
        />

        <x-mary-input
            wire:model="password_confirmation"
            label="{{ __('Konfirmasi password') }}"
            type="password"
            icon="o-lock-closed"
            required
            autocomplete="new-password"
        />

        <x-mary-button
            type="submit"
            label="{{ __('Reset password') }}"
            class="btn-primary w-full"
            spinner="submit"
            data-test="reset-password-submit"
        />
    </form>

    <div class="text-center text-sm text-base-content/70 space-y-1">
        <div>
            {{ __('Tidak dapat kode?') }}
            <button type="button" wire:click="resend" class="link link-primary">
                {{ __('Kirim ulang') }}
            </button>
        </div>
        <div>
            <a href="{{ route('login') }}" wire:navigate class="link">{{ __('Kembali ke halaman masuk') }}</a>
        </div>
    </div>
</div>
