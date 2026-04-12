<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $setupComplete = false;

    public bool $modalOpen = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
        $this->modalOpen = true;
    }

    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;
            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->setupComplete = true;

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');
        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showVerificationStep',
            'setupComplete',
            'modalOpen',
        );

        $this->resetErrorBag();
    }
}; ?>

<x-mary-modal wire:model="modalOpen" title="{{ __('Aktifkan autentikasi dua faktor') }}" subtitle="{{ __('Scan QR atau input setup key di aplikasi autentikator.') }}" separator box-class="max-w-md">
    @if ($showVerificationStep)
        <div class="space-y-5">
            <p class="text-sm text-base-content/70">{{ __('Masukkan kode 6-digit dari aplikasi autentikator.') }}</p>

            <x-mary-input
                wire:model="code"
                label="{{ __('Kode OTP') }}"
                inputmode="numeric"
                maxlength="6"
                autocomplete="one-time-code"
                :error="$errors->first('code')"
            />
        </div>

        <x-slot:actions>
            <x-mary-button label="{{ __('Kembali') }}" wire:click="resetVerification" />
            <x-mary-button label="{{ __('Konfirmasi') }}" class="btn-primary" wire:click="confirmTwoFactor" spinner="confirmTwoFactor" />
        </x-slot:actions>
    @else
        @error('setupData')
            <div class="alert alert-error">{{ $message }}</div>
        @enderror

        <div class="flex justify-center">
            <div class="relative w-64 overflow-hidden rounded-lg border border-base-300 aspect-square">
                @empty($qrCodeSvg)
                    <div class="absolute inset-0 flex items-center justify-center bg-base-100 animate-pulse">
                        <x-mary-icon name="o-arrow-path" class="animate-spin" />
                    </div>
                @else
                    <div class="flex items-center justify-center h-full p-4 bg-white">
                        <div>{!! $qrCodeSvg !!}</div>
                    </div>
                @endempty
            </div>
        </div>

        <div class="mt-5">
            <p class="text-xs text-base-content/60 mb-2">{{ __('atau masukkan kode manual:') }}</p>
            <div
                x-data="{
                    copied: false,
                    async copy() {
                        try {
                            await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                            this.copied = true;
                            setTimeout(() => this.copied = false, 1500);
                        } catch (e) {}
                    }
                }"
                class="flex items-stretch rounded-lg border border-base-300"
            >
                @empty($manualSetupKey)
                    <div class="flex items-center justify-center w-full p-3">
                        <x-mary-icon name="o-arrow-path" class="animate-spin" />
                    </div>
                @else
                    <input type="text" readonly value="{{ $manualSetupKey }}" class="w-full p-3 bg-transparent outline-none font-mono text-sm" />
                    <button @click="copy()" type="button" class="px-3 border-l border-base-300 cursor-pointer">
                        <x-mary-icon name="o-document-duplicate" x-show="!copied" />
                        <x-mary-icon name="o-check" x-show="copied" class="text-success" />
                    </button>
                @endempty
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button
                :disabled="$errors->has('setupData')"
                label="{{ __('Lanjutkan') }}"
                class="btn-primary"
                wire:click="showVerificationIfNecessary"
            />
        </x-slot:actions>
    @endif
</x-mary-modal>
