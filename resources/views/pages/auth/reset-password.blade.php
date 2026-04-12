<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Reset password')" :description="__('Silakan masukkan password baru Anda')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}" />

            <x-mary-input
                name="email"
                value="{{ request('email') }}"
                label="{{ __('Email') }}"
                type="email"
                icon="o-envelope"
                required
                autocomplete="email"
                :error="$errors->first('email')"
            />

            <x-mary-input
                name="password"
                label="{{ __('Password baru') }}"
                type="password"
                icon="o-lock-closed"
                required
                autocomplete="new-password"
                :error="$errors->first('password')"
            />

            <x-mary-input
                name="password_confirmation"
                label="{{ __('Konfirmasi password') }}"
                type="password"
                icon="o-lock-closed"
                required
                autocomplete="new-password"
            />

            <x-mary-button type="submit" label="{{ __('Reset password') }}" class="btn-primary w-full" spinner data-test="reset-password-button" />
        </form>
    </div>
</x-layouts::auth>
