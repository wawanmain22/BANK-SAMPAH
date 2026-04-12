<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules, Toast;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->success(__('Password diperbarui.'));
    }

    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Password')" :subheading="__('Gunakan password panjang dan acak untuk menjaga keamanan akun')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-5">
            <x-mary-input wire:model="current_password" label="{{ __('Password saat ini') }}" icon="o-lock-closed" type="password" required autocomplete="current-password" :error="$errors->first('current_password')" />
            <x-mary-input wire:model="password" label="{{ __('Password baru') }}" icon="o-lock-closed" type="password" required autocomplete="new-password" :error="$errors->first('password')" />
            <x-mary-input wire:model="password_confirmation" label="{{ __('Konfirmasi password') }}" icon="o-lock-closed" type="password" required autocomplete="new-password" />

            <x-mary-button type="submit" label="{{ __('Simpan') }}" class="btn-primary" spinner="updatePassword" data-test="update-password-button" />
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <h3 class="text-base font-semibold">{{ __('Autentikasi dua faktor') }}</h3>
                <p class="text-sm text-base-content/60">{{ __('Kelola pengaturan 2FA akun Anda') }}</p>

                <div class="mt-4 space-y-4 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <p>{{ __('Login akan meminta kode OTP dari aplikasi autentikator Anda.') }}</p>
                        <x-mary-button label="{{ __('Nonaktifkan 2FA') }}" class="btn-error" wire:click="disable" spinner="disable" />
                        <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                    @else
                        <p class="text-base-content/70">{{ __('Saat diaktifkan, login akan meminta kode OTP dari aplikasi autentikator.') }}</p>
                        <x-mary-button
                            label="{{ __('Aktifkan 2FA') }}"
                            class="btn-primary"
                            wire:click="$dispatch('start-two-factor-setup')"
                        />
                        <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                    @endif
                </div>
            </section>
        @endif
    </x-pages::settings.layout>
</section>
