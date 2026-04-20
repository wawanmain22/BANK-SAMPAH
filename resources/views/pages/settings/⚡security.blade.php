<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules, Toast;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

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
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Password')" :subheading="__('Gunakan password panjang dan acak untuk menjaga keamanan akun')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-5">
            <x-mary-input
                wire:model="current_password"
                label="{{ __('Password saat ini') }}"
                icon="o-lock-closed"
                type="password"
                required
                autocomplete="current-password"
                :error="$errors->first('current_password')"
            />
            <x-mary-input
                wire:model="password"
                label="{{ __('Password baru') }}"
                icon="o-lock-closed"
                type="password"
                required
                autocomplete="new-password"
                :error="$errors->first('password')"
            />
            <x-mary-input
                wire:model="password_confirmation"
                label="{{ __('Konfirmasi password baru') }}"
                icon="o-lock-closed"
                type="password"
                required
                autocomplete="new-password"
            />

            <x-mary-button
                type="submit"
                label="{{ __('Simpan') }}"
                class="btn-primary"
                spinner="updatePassword"
                data-test="update-password-button"
            />
        </form>
    </x-pages::settings.layout>
</section>
