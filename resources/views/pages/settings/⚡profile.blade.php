<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules, Toast;

    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->success(__('Profil diperbarui.'));
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        $this->info(__('Link verifikasi baru telah dikirim ke email Anda.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Profil')" :subheading="__('Perbarui nama dan alamat email Anda')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-5">
            <x-mary-input wire:model="name" label="{{ __('Nama') }}" icon="o-user" required autofocus autocomplete="name" :error="$errors->first('name')" />

            <div>
                <x-mary-input wire:model="email" label="{{ __('Email') }}" icon="o-envelope" type="email" required autocomplete="email" :error="$errors->first('email')" />

                @if ($this->hasUnverifiedEmail)
                    <p class="mt-3 text-sm text-base-content/70">
                        {{ __('Email Anda belum terverifikasi.') }}
                        <button type="button" wire:click.prevent="resendVerificationNotification" class="link link-primary">
                            {{ __('Kirim ulang email verifikasi.') }}
                        </button>
                    </p>
                @endif
            </div>

            <x-mary-button type="submit" label="{{ __('Simpan') }}" class="btn-primary" spinner="updateProfileInformation" data-test="update-profile-button" />
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
