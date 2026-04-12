<x-layouts::auth :title="__('Autentikasi dua faktor')">
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
            }"
        >
            <div x-show="!showRecoveryInput">
                <x-auth-header
                    :title="__('Kode autentikasi')"
                    :description="__('Masukkan kode 6-digit dari aplikasi autentikator Anda.')"
                />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header
                    :title="__('Kode recovery')"
                    :description="__('Masukkan salah satu kode recovery darurat untuk konfirmasi akun.')"
                />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-5">
                @csrf

                <div class="space-y-5 text-center">
                    <div x-show="!showRecoveryInput">
                        <x-mary-input
                            name="code"
                            label="{{ __('Kode OTP') }}"
                            inputmode="numeric"
                            maxlength="6"
                            autocomplete="one-time-code"
                            autofocus
                            :error="$errors->first('code')"
                        />
                    </div>

                    <div x-show="showRecoveryInput">
                        <x-mary-input
                            name="recovery_code"
                            label="{{ __('Kode recovery') }}"
                            x-ref="recovery_code"
                            autocomplete="one-time-code"
                            :error="$errors->first('recovery_code')"
                        />
                    </div>

                    <x-mary-button type="submit" label="{{ __('Lanjutkan') }}" class="btn-primary w-full" spinner />
                </div>

                <div class="mt-5 text-sm text-center">
                    <span class="text-base-content/60">{{ __('atau Anda bisa') }}</span>
                    <button type="button" class="ms-1 link link-primary" @click="showRecoveryInput = !showRecoveryInput">
                        <span x-show="!showRecoveryInput">{{ __('masuk dengan kode recovery') }}</span>
                        <span x-show="showRecoveryInput">{{ __('masuk dengan kode autentikasi') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
