<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Buat akun baru')" :description="__('Isi data di bawah untuk mendaftar')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
            @csrf

            <x-mary-input
                name="name"
                label="{{ __('Nama') }}"
                :value="old('name')"
                icon="o-user"
                required
                autofocus
                autocomplete="name"
                placeholder="{{ __('Nama lengkap') }}"
                :error="$errors->first('name')"
            />

            <x-mary-input
                name="email"
                label="{{ __('Email') }}"
                :value="old('email')"
                type="email"
                icon="o-envelope"
                required
                autocomplete="email"
                placeholder="email@example.com"
                :error="$errors->first('email')"
            />

            <x-mary-input
                name="password"
                label="{{ __('Password') }}"
                type="password"
                icon="o-lock-closed"
                required
                autocomplete="new-password"
                placeholder="{{ __('Password') }}"
                :error="$errors->first('password')"
            />

            <x-mary-input
                name="password_confirmation"
                label="{{ __('Konfirmasi password') }}"
                type="password"
                icon="o-lock-closed"
                required
                autocomplete="new-password"
                placeholder="{{ __('Ulangi password') }}"
            />

            <x-mary-button type="submit" label="{{ __('Daftar') }}" class="btn-primary w-full" spinner data-test="register-user-button" />
        </form>

        <div class="text-center text-sm text-base-content/70">
            <span>{{ __('Sudah punya akun?') }}</span>
            <a href="{{ route('login') }}" wire:navigate class="link link-primary">{{ __('Masuk') }}</a>
        </div>
    </div>
</x-layouts::auth>
