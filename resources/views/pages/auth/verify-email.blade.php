<x-layouts::auth :title="__('Verifikasi email')">
    <div class="mt-4 flex flex-col gap-6">
        <p class="text-center text-sm text-base-content/70">
            {{ __('Silakan verifikasi alamat email Anda dengan mengklik link yang telah kami kirim.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <p class="text-center font-medium text-success">
                {{ __('Link verifikasi baru telah dikirim ke email Anda.') }}
            </p>
        @endif

        <div class="flex flex-col items-center gap-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <x-mary-button type="submit" label="{{ __('Kirim ulang email verifikasi') }}" class="btn-primary w-full" spinner />
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-mary-button type="submit" label="{{ __('Keluar') }}" class="btn-ghost btn-sm" data-test="logout-button" />
            </form>
        </div>
    </div>
</x-layouts::auth>
