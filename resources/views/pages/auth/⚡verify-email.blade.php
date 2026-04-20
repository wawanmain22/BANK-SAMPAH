<?php

use App\Models\EmailOtp;
use App\Services\EmailOtpService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Layout('layouts.auth')] #[Title('Verifikasi email')] class extends Component {
    use Toast;

    public string $code = '';

    public function mount(): void
    {
        if (Auth::user()?->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard'), navigate: true);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ];
    }

    public function submit(EmailOtpService $service): void
    {
        $this->validate();

        $user = Auth::user();

        if (! $user) {
            $this->error(__('Silakan login ulang.'));

            return;
        }

        try {
            $service->verify($user->email, EmailOtp::PURPOSE_EMAIL_VERIFICATION, $this->code);
        } catch (\InvalidArgumentException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        session()->flash('status', __('Email berhasil diverifikasi.'));

        $this->redirectIntended(default: route('dashboard'), navigate: true);
    }

    public function resend(EmailOtpService $service): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        try {
            $service->send($user->email, EmailOtp::PURPOSE_EMAIL_VERIFICATION);
            $this->success(__('Kode OTP baru telah dikirim ke email Anda.'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Verifikasi email')"
        :description="__('Masukkan kode OTP 6 digit yang kami kirim ke :email', ['email' => auth()->user()?->email ?? ''])"
    />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="submit" class="flex flex-col gap-5">
        <x-mary-input
            wire:model="code"
            label="{{ __('Kode OTP') }}"
            icon="o-key"
            maxlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            placeholder="123456"
            required
            autofocus
            :error="$errors->first('code')"
        />

        <x-mary-button
            type="submit"
            label="{{ __('Verifikasi') }}"
            class="btn-primary w-full"
            spinner="submit"
            data-test="verify-email-submit"
        />
    </form>

    <div class="flex flex-col items-center gap-2 text-sm text-base-content/70">
        <div>
            {{ __('Tidak dapat kode?') }}
            <button type="button" wire:click="resend" class="link link-primary">
                {{ __('Kirim ulang') }}
            </button>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="link">{{ __('Keluar') }}</button>
        </form>
    </div>
</div>
