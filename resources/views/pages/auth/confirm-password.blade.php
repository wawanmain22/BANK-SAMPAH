@php
    $authenticated = auth()->check();
@endphp

@if ($authenticated)
    <x-layouts::app :title="__('Konfirmasi password')">
        <section class="w-full">
            @include('partials.settings-heading')

            <x-pages::settings.layout :heading="__('Konfirmasi password')" :subheading="__('Ini area sensitif. Konfirmasi password sebelum melanjutkan.')">
                <x-auth-session-status class="text-center" :status="session('status')" />

                <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5 mt-4">
                    @csrf

                    <x-mary-input
                        name="password"
                        label="{{ __('Password') }}"
                        type="password"
                        icon="o-lock-closed"
                        required
                        autocomplete="current-password"
                        :error="$errors->first('password')"
                    />

                    <x-mary-button type="submit" label="{{ __('Konfirmasi') }}" class="btn-primary" spinner data-test="confirm-password-button" />
                </form>
            </x-pages::settings.layout>
        </section>
    </x-layouts::app>
@else
    <x-layouts::auth :title="__('Konfirmasi password')">
        <div class="flex flex-col gap-6">
            <x-auth-header
                :title="__('Konfirmasi password')"
                :description="__('Ini area sensitif. Konfirmasi password sebelum melanjutkan.')"
            />

            <x-auth-session-status class="text-center" :status="session('status')" />

            <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
                @csrf

                <x-mary-input
                    name="password"
                    label="{{ __('Password') }}"
                    type="password"
                    icon="o-lock-closed"
                    required
                    autocomplete="current-password"
                    :error="$errors->first('password')"
                />

                <x-mary-button type="submit" label="{{ __('Konfirmasi') }}" class="btn-primary w-full" spinner data-test="confirm-password-button" />
            </form>
        </div>
    </x-layouts::auth>
@endif
