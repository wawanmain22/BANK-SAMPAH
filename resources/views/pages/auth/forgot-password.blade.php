<x-layouts::auth :title="__('Lupa password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Lupa password')" :description="__('Masukkan email untuk mendapatkan link reset password')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
            @csrf

            <x-mary-input
                name="email"
                label="{{ __('Email') }}"
                type="email"
                icon="o-envelope"
                required
                autofocus
                placeholder="email@example.com"
                :error="$errors->first('email')"
            />

            <x-mary-button type="submit" label="{{ __('Kirim link reset') }}" class="btn-primary w-full" spinner data-test="email-password-reset-link-button" />
        </form>

        <div class="text-center text-sm text-base-content/70">
            <span>{{ __('Atau kembali ke') }}</span>
            <a href="{{ route('login') }}" wire:navigate class="link link-primary">{{ __('halaman masuk') }}</a>
        </div>
    </div>
</x-layouts::auth>
