<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Masuk ke akun Anda')" :description="__('Masukkan email dan password untuk masuk')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <x-mary-input
                name="email"
                label="{{ __('Email') }}"
                :value="old('email')"
                type="email"
                icon="o-envelope"
                required
                autofocus
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
                autocomplete="current-password"
                placeholder="{{ __('Password') }}"
                :error="$errors->first('password')"
            />

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate class="link link-primary text-sm self-end -mt-2">
                    {{ __('Lupa password?') }}
                </a>
            @endif

            <label class="label cursor-pointer justify-start gap-3">
                <input type="checkbox" name="remember" value="1" class="checkbox checkbox-primary checkbox-sm" @checked(old('remember')) />
                <span class="label-text">{{ __('Ingat saya') }}</span>
            </label>

            <x-mary-button type="submit" label="{{ __('Masuk') }}" class="btn-primary w-full" spinner data-test="login-button" />
        </form>

        @if (Route::has('register'))
            <div class="text-center text-sm text-base-content/70">
                <span>{{ __('Belum punya akun?') }}</span>
                <a href="{{ route('register') }}" wire:navigate class="link link-primary">{{ __('Daftar') }}</a>
            </div>
        @endif
    </div>
</x-layouts::auth>
