<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<div
    class="py-6 space-y-4 rounded-xl border border-base-300 bg-base-100"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="px-6 space-y-1">
        <div class="flex items-center gap-2">
            <x-mary-icon name="o-lock-closed" class="size-4" />
            <h4 class="text-base font-semibold">{{ __('Kode recovery 2FA') }}</h4>
        </div>
        <p class="text-sm text-base-content/60">
            {{ __('Kode recovery membantu Anda kembali masuk jika kehilangan perangkat 2FA. Simpan di password manager yang aman.') }}
        </p>
    </div>

    <div class="px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-mary-button
                x-show="!showRecoveryCodes"
                icon="o-eye"
                class="btn-primary btn-sm"
                label="{{ __('Lihat kode recovery') }}"
                @click="showRecoveryCodes = true"
            />
            <x-mary-button
                x-show="showRecoveryCodes"
                icon="o-eye-slash"
                class="btn-primary btn-sm"
                label="{{ __('Sembunyikan') }}"
                @click="showRecoveryCodes = false"
            />

            @if (filled($recoveryCodes))
                <x-mary-button
                    x-show="showRecoveryCodes"
                    icon="o-arrow-path"
                    class="btn-sm"
                    label="{{ __('Regenerate kode') }}"
                    wire:click="regenerateRecoveryCodes"
                    spinner
                />
            @endif
        </div>

        <div x-show="showRecoveryCodes" x-transition class="mt-3 space-y-3">
            @error('recoveryCodes')
                <div class="alert alert-error text-sm">{{ $message }}</div>
            @enderror

            @if (filled($recoveryCodes))
                <div class="grid gap-1 p-4 font-mono text-sm rounded-lg bg-base-200" role="list">
                    @foreach($recoveryCodes as $code)
                        <div role="listitem" class="select-text" wire:loading.class="opacity-50 animate-pulse">
                            {{ $code }}
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-base-content/60">
                    {{ __('Tiap kode hanya bisa dipakai sekali. Jika ingin kode baru, klik Regenerate.') }}
                </p>
            @endif
        </div>
    </div>
</div>
