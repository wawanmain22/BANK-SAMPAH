<?php

use App\Models\EmailOtp;
use App\Models\User;
use App\Services\EmailOtpService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts.auth')] #[Title('Lupa password')] class extends Component {
    use Toast;

    public string $email = '';

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function submit(EmailOtpService $service): void
    {
        $this->validate();

        $email = strtolower(trim($this->email));

        // Always redirect to verify step even if user doesn't exist — don't leak existence.
        if (User::where('email', $email)->exists()) {
            try {
                $service->send($email, EmailOtp::PURPOSE_PASSWORD_RESET);
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());

                return;
            } catch (\InvalidArgumentException $e) {
                $this->error($e->getMessage());

                return;
            }
        }

        session()->flash('status', __('Kode OTP telah dikirim ke email Anda jika akun terdaftar.'));

        $this->redirect(route('password.reset', ['email' => $email]), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Lupa password')"
        :description="__('Masukkan email Anda. Kami akan kirim kode OTP 6 digit ke email untuk reset password.')"
    />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="submit" class="flex flex-col gap-5">
        <x-mary-input
            wire:model="email"
            label="{{ __('Email') }}"
            type="email"
            icon="o-envelope"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
            :error="$errors->first('email')"
        />

        <x-mary-button
            type="submit"
            label="{{ __('Kirim kode OTP') }}"
            class="btn-primary w-full"
            spinner="submit"
            data-test="forgot-password-submit"
        />
    </form>

    <div class="text-center text-sm text-base-content/70">
        <span>{{ __('Atau kembali ke') }}</span>
        <a href="{{ route('login') }}" wire:navigate class="link link-primary">{{ __('halaman masuk') }}</a>
    </div>
</div>
