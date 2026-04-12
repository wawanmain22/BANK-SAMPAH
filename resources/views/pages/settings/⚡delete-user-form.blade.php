<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-4">
    <div class="relative mb-3">
        <h3 class="text-base font-semibold">{{ __('Hapus akun') }}</h3>
        <p class="text-sm text-base-content/60">{{ __('Hapus akun beserta seluruh data Anda') }}</p>
    </div>

    <x-mary-button
        label="{{ __('Hapus akun') }}"
        class="btn-error"
        @click="$dispatch('open-delete-user')"
        data-test="delete-user-button"
    />

    <livewire:pages::settings.delete-user-modal />
</section>
