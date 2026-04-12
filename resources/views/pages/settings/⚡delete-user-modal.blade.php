<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    public bool $confirmModal = false;

    #[On('open-delete-user')]
    public function open(): void
    {
        $this->confirmModal = true;
    }

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-mary-modal wire:model="confirmModal" title="{{ __('Yakin ingin menghapus akun?') }}" subtitle="{{ __('Tindakan ini tidak dapat dibatalkan.') }}" separator box-class="max-w-lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-4">
        <p class="text-sm text-base-content/70">
            {{ __('Setelah akun dihapus, seluruh data akan hilang permanen. Masukkan password Anda untuk konfirmasi.') }}
        </p>

        <x-mary-input
            wire:model="password"
            label="{{ __('Password') }}"
            icon="o-lock-closed"
            type="password"
            :error="$errors->first('password')"
        />

        <x-slot:actions>
            <x-mary-button label="{{ __('Batal') }}" @click="$wire.confirmModal = false" />
            <x-mary-button
                type="submit"
                label="{{ __('Hapus akun') }}"
                class="btn-error"
                spinner="deleteUser"
                data-test="confirm-delete-user-button"
            />
        </x-slot:actions>
    </form>
</x-mary-modal>
