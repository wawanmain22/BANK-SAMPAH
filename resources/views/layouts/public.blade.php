@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="greennature">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>
        {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Bank Sampah') : config('app.name', 'Bank Sampah') }}
    </title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml" />

    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=lato:400,500,700,900" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col font-sans antialiased bg-base-200 text-base-content">

    {{-- Main nav --}}
    <header class="sticky top-0 z-30 bg-secondary text-secondary-content shadow-sm">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="flex aspect-square size-11 items-center justify-center rounded-md bg-primary text-primary-content">
                    <x-app-logo-icon class="size-6 fill-current" />
                </div>
                <div class="leading-tight">
                    <div class="text-lg font-bold tracking-wide uppercase">{{ config('app.name', 'Bank Sampah') }}</div>
                    <div class="text-[11px] uppercase tracking-[0.2em] text-secondary-content/60">Eco Operational</div>
                </div>
            </a>

            <nav class="hidden items-center gap-7 text-sm font-semibold tracking-wide uppercase md:flex">
                <a href="{{ route('home') }}" class="hover:text-accent transition-colors">{{ __('Beranda') }}</a>
                <a href="{{ route('public.edukasi.index') }}" class="hover:text-accent transition-colors">{{ __('Edukasi') }}</a>
                <a href="{{ route('public.merchandise.index') }}" class="hover:text-accent transition-colors">{{ __('Merchandise') }}</a>
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-sm border-none bg-accent text-accent-content hover:brightness-95 font-bold uppercase tracking-wider px-5">
                        {{ __('Dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm border-none bg-accent text-accent-content hover:brightness-95 font-bold uppercase tracking-wider px-5">
                        {{ __('Masuk') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="mt-auto text-[color:var(--color-footer-content)]" style="background-color: var(--color-footer);">
        <div class="mx-auto max-w-6xl px-4 py-12 grid grid-cols-1 gap-8 md:grid-cols-4">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex aspect-square size-10 items-center justify-center rounded-md bg-primary text-primary-content">
                        <x-app-logo-icon class="size-5 fill-current" />
                    </div>
                    <div class="leading-tight text-white">
                        <div class="font-bold uppercase">{{ config('app.name', 'Bank Sampah') }}</div>
                        <div class="text-[11px] uppercase tracking-[0.2em] opacity-60">Eco Operational</div>
                    </div>
                </div>
                <p class="text-sm leading-relaxed">
                    {{ __('Sistem operasional bank sampah Pak Toni — mengelola sampah jadi saldo, poin, dan produk olahan yang bernilai bagi masyarakat.') }}
                </p>
            </div>

            <div>
                <h4 class="font-bold uppercase tracking-[0.15em] text-white mb-4">{{ __('Kontak') }}</h4>
                <ul class="space-y-2 text-sm">
                    <li class="flex gap-2"><x-mary-icon name="o-map-pin" class="size-4 mt-0.5 shrink-0 text-accent" /> <span>Jl. Melati No. 1, Bandung</span></li>
                    <li class="flex gap-2"><x-mary-icon name="o-phone" class="size-4 mt-0.5 shrink-0 text-accent" /> +62 812-3456-7890</li>
                    <li class="flex gap-2"><x-mary-icon name="o-envelope" class="size-4 mt-0.5 shrink-0 text-accent" /> halo@banksampah.test</li>
                </ul>
            </div>

            <div>
                <h4 class="font-bold uppercase tracking-[0.15em] text-white mb-4">{{ __('Navigasi') }}</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-accent">{{ __('Beranda') }}</a></li>
                    <li><a href="{{ route('public.edukasi.index') }}" class="hover:text-accent">{{ __('Edukasi') }}</a></li>
                    <li><a href="{{ route('public.merchandise.index') }}" class="hover:text-accent">{{ __('Merchandise') }}</a></li>
                    @guest
                        <li><a href="{{ route('login') }}" class="hover:text-accent">{{ __('Masuk') }}</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-accent">{{ __('Daftar') }}</a></li>
                    @endguest
                </ul>
            </div>

            <div>
                <h4 class="font-bold uppercase tracking-[0.15em] text-white mb-4">{{ __('Ikut Berpartisipasi') }}</h4>
                <p class="text-sm leading-relaxed mb-4">
                    {{ __('Daftar jadi nasabah atau donasikan sampahmu. Setiap kg berharga.') }}
                </p>
                @guest
                    <a href="{{ route('register') }}" class="btn btn-sm border-none bg-accent text-accent-content hover:brightness-95 font-bold uppercase tracking-wider">
                        {{ __('Daftar Sekarang') }}
                    </a>
                @endguest
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="mx-auto max-w-6xl px-4 py-4 flex flex-col-reverse md:flex-row items-center justify-between gap-3 text-xs">
                <span>&copy; {{ now()->year }} {{ config('app.name', 'Bank Sampah') }}. {{ __('Semua hak dilindungi.') }}</span>
                <div class="flex items-center gap-3">
                    <span class="opacity-70">{{ __('Membangun ekosistem daur ulang yang lestari') }}</span>
                </div>
            </div>
        </div>
    </footer>

    <x-mary-toast />
</body>
</html>
