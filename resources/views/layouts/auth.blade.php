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
<body class="min-h-screen font-sans antialiased bg-base-200 text-base-content">
    <div class="absolute top-4 left-4">
        <a href="{{ route('home') }}" class="btn btn-ghost btn-sm gap-1" wire:navigate>
            <x-mary-icon name="o-arrow-left" class="size-4" />
            {{ __('Kembali ke beranda') }}
        </a>
    </div>

    <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex w-full max-w-md flex-col gap-6">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2" wire:navigate>
                <div class="flex aspect-square size-10 items-center justify-center rounded-md bg-primary text-primary-content">
                    <x-app-logo-icon class="size-6 fill-current" />
                </div>
                <span class="text-sm font-semibold">{{ config('app.name', 'Bank Sampah') }}</span>
            </a>

            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    <x-mary-toast />
</body>
</html>
