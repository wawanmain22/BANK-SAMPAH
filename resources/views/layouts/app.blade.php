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

@php
    $isStaff = auth()->user()?->isAdmin() || auth()->user()?->isOwner();

    $dashboardHref = $isStaff ? route('admin.dashboard') : route('dashboard');
    $dashboardActive = $isStaff
        ? request()->routeIs('admin.dashboard')
        : request()->routeIs('dashboard');

    $navSections = [
        [
            'items' => [
                ['label' => __('Dashboard'), 'icon' => 'o-home', 'href' => $dashboardHref, 'active' => $dashboardActive],
            ],
        ],
    ];

    if (! $isStaff && auth()->user()?->isNasabah()) {
        $navSections[] = [
            'label' => __('Akun Saya'),
            'items' => [
                ['label' => __('Saldo'), 'icon' => 'o-banknotes', 'href' => route('nasabah.saldo'), 'active' => request()->routeIs('nasabah.saldo')],
                ['label' => __('Transaksi Nabung'), 'icon' => 'o-arrow-trending-up', 'href' => route('nasabah.transaksi'), 'active' => request()->routeIs('nasabah.transaksi')],
                ['label' => __('Pencairan'), 'icon' => 'o-wallet', 'href' => route('nasabah.pencairan'), 'active' => request()->routeIs('nasabah.pencairan')],
                ['label' => __('Histori Poin'), 'icon' => 'o-sparkles', 'href' => route('nasabah.poin'), 'active' => request()->routeIs('nasabah.poin')],
            ],
        ];
    }

    if ($isStaff) {
        $inventorySource = request()->query('source', 'nabung');
        $inventoryOnIndex = request()->routeIs('admin.inventory.*');

        $staffGroups = [
            [
                'label' => __('Manajemen'),
                'key' => 'manajemen',
                'default_open' => false,
                'items' => [
                    ['label' => __('Nasabah'), 'icon' => 'o-users', 'href' => route('admin.nasabah.index'), 'active' => request()->routeIs('admin.nasabah.*')],
                ],
            ],
            [
                'label' => __('Master Data'),
                'key' => 'master',
                'default_open' => true,
                'items' => [
                    ['label' => __('Kategori Sampah'), 'icon' => 'o-tag', 'href' => route('admin.waste-category.index'), 'active' => request()->routeIs('admin.waste-category.*')],
                    ['label' => __('Barang Sampah'), 'icon' => 'o-hashtag', 'href' => route('admin.waste-item.index'), 'active' => request()->routeIs('admin.waste-item.*')],
                    ['label' => __('Harga Sampah'), 'icon' => 'o-banknotes', 'href' => route('admin.waste-price.index'), 'active' => request()->routeIs('admin.waste-price.*')],
                    ['label' => __('Mitra'), 'icon' => 'o-building-office', 'href' => route('admin.partner.index'), 'active' => request()->routeIs('admin.partner.*')],
                    ['label' => __('Produk'), 'icon' => 'o-cube', 'href' => route('admin.product.index'), 'active' => request()->routeIs('admin.product.*')],
                    ['label' => __('Master Poin'), 'icon' => 'o-star', 'href' => route('admin.point-rule.index'), 'active' => request()->routeIs('admin.point-rule.*')],
                ],
            ],
            [
                'label' => __('Transaksi'),
                'key' => 'transaksi',
                'default_open' => true,
                'items' => [
                    ['label' => __('Nabung'), 'icon' => 'o-arrow-trending-up', 'href' => route('admin.saving.index'), 'active' => request()->routeIs('admin.saving.*')],
                    ['label' => __('Sedekah'), 'icon' => 'o-heart', 'href' => route('admin.sedekah.index'), 'active' => request()->routeIs('admin.sedekah.*')],
                    ['label' => __('Penjualan ke Mitra'), 'icon' => 'o-truck', 'href' => route('admin.sales.index'), 'active' => request()->routeIs('admin.sales.*')],
                    ['label' => __('Pengolahan'), 'icon' => 'o-cog-8-tooth', 'href' => route('admin.processing.index'), 'active' => request()->routeIs('admin.processing.*')],
                    ['label' => __('Penjualan Produk'), 'icon' => 'o-shopping-bag', 'href' => route('admin.product-sale.index'), 'active' => request()->routeIs('admin.product-sale.*')],
                ],
            ],
            [
                'label' => __('Inventory'),
                'key' => 'inventory',
                'default_open' => true,
                'items' => [
                    ['label' => __('Inventory Nabung'), 'icon' => 'o-archive-box', 'href' => route('admin.inventory.index', ['source' => 'nabung']), 'active' => $inventoryOnIndex && $inventorySource === 'nabung'],
                    ['label' => __('Inventory Sedekah'), 'icon' => 'o-gift', 'href' => route('admin.inventory.index', ['source' => 'sedekah']), 'active' => $inventoryOnIndex && $inventorySource === 'sedekah'],
                ],
            ],
            [
                'label' => __('Keuangan'),
                'key' => 'keuangan',
                'default_open' => true,
                'items' => [
                    ['label' => __('Release Saldo'), 'icon' => 'o-arrow-right-circle', 'href' => route('admin.release.index'), 'active' => request()->routeIs('admin.release.*')],
                    ['label' => __('Pencairan'), 'icon' => 'o-wallet', 'href' => route('admin.withdrawal.index'), 'active' => request()->routeIs('admin.withdrawal.*')],
                ],
            ],
            [
                'label' => __('Loyalti'),
                'key' => 'loyalti',
                'default_open' => false,
                'items' => [
                    ['label' => __('Tukar Poin → Produk'), 'icon' => 'o-gift', 'href' => route('admin.redemption.index'), 'active' => request()->routeIs('admin.redemption.*')],
                    ['label' => __('Tukar Poin → Saldo'), 'icon' => 'o-banknotes', 'href' => route('admin.point-cash-out.index'), 'active' => request()->routeIs('admin.point-cash-out.*')],
                    ['label' => __('Histori Poin'), 'icon' => 'o-sparkles', 'href' => route('admin.point-history.index'), 'active' => request()->routeIs('admin.point-history.*')],
                ],
            ],
            [
                'label' => __('Konten'),
                'key' => 'konten',
                'default_open' => false,
                'items' => [
                    ['label' => __('Edukasi'), 'icon' => 'o-book-open', 'href' => route('admin.article.index'), 'active' => request()->routeIs('admin.article.*')],
                ],
            ],
        ];

        foreach ($staffGroups as $group) {
            $navSections[] = [
                'label' => $group['label'],
                'key' => $group['key'],
                'collapsible' => true,
                'default_open' => $group['default_open'],
                'items' => $group['items'],
            ];
        }
    }

    $settingsActive = request()->routeIs('settings.index') || request()->routeIs('profile.edit') || request()->routeIs('security.edit') || request()->routeIs('appearance.edit');
@endphp

<div class="drawer lg:drawer-open">
    <input id="main-drawer" type="checkbox" class="drawer-toggle" />

    <div class="drawer-content flex min-h-screen flex-col">
        {{-- Mobile top navbar --}}
        <header class="lg:hidden sticky top-0 z-30 flex items-center justify-between bg-secondary text-secondary-content px-4 py-3 shadow-sm">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <div class="flex aspect-square size-9 items-center justify-center rounded-md bg-primary text-primary-content">
                    <x-app-logo-icon class="size-5 fill-current" />
                </div>
                <div class="leading-tight">
                    <div class="text-sm font-bold uppercase tracking-wide">{{ config('app.name', 'Bank Sampah') }}</div>
                </div>
            </a>
            <label for="main-drawer" class="btn btn-square btn-ghost btn-sm text-secondary-content hover:bg-secondary-content/10" aria-label="Open menu">
                <x-mary-icon name="o-bars-3" />
            </label>
        </header>

        <main class="flex-1">
            <div class="mx-auto w-full max-w-7xl p-4 md:p-6 lg:p-8">
                {{ $slot }}
            </div>
        </main>
    </div>

    {{-- Sidebar (scroll position persisted in localStorage — survives wire:navigate + hard refresh) --}}
    <aside class="drawer-side z-40">
        <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

        <nav class="flex h-screen w-64 flex-col bg-secondary text-secondary-content">
            {{-- Brand --}}
            <div class="flex h-16 items-center justify-between gap-2 px-4 border-b border-secondary-content/10">
                <a href="{{ route('home') }}" class="flex items-center gap-2 min-w-0" wire:navigate>
                    <div class="flex aspect-square size-9 items-center justify-center rounded-md bg-primary text-primary-content shrink-0">
                        <x-app-logo-icon class="size-5 fill-current" />
                    </div>
                    <div class="leading-tight min-w-0">
                        <div class="text-sm font-bold uppercase tracking-wide truncate">{{ config('app.name', 'Bank Sampah') }}</div>
                        <div class="text-[10px] uppercase tracking-[0.2em] text-secondary-content/60">Eco Operational</div>
                    </div>
                </a>
                <a
                    href="{{ route('home') }}"
                    class="btn btn-square btn-ghost btn-sm shrink-0 text-secondary-content hover:bg-secondary-content/10"
                    title="{{ __('Ke Beranda Situs') }}"
                >
                    <x-mary-icon name="o-home" class="size-5" />
                </a>
            </div>

            {{-- Menu (scroll position persisted across navigate + refresh via localStorage) --}}
            <div
                class="flex-1 overflow-y-auto py-3"
                x-data="{
                    key: 'nav:scroll',
                    init() {
                        const saved = parseInt(localStorage.getItem(this.key) || '0', 10);
                        if (saved > 0) {
                            this.$nextTick(() => { this.$el.scrollTop = saved; });
                        }
                        let raf = null;
                        this.$el.addEventListener('scroll', () => {
                            if (raf) cancelAnimationFrame(raf);
                            raf = requestAnimationFrame(() => {
                                localStorage.setItem(this.key, String(this.$el.scrollTop));
                            });
                        }, { passive: true });
                    },
                }">
                @foreach ($navSections as $section)
                    @php
                        $hasActive = collect($section['items'] ?? [])->contains(fn ($i) => $i['active'] ?? false);
                        $collapsible = $section['collapsible'] ?? false;
                        $sectionKey = $section['key'] ?? ($section['label'] ?? 'nav');
                    @endphp

                    @if ($collapsible && ! empty($section['label']))
                        <div
                            x-data="{
                                key: 'nav:{{ $sectionKey }}',
                                hasActive: {{ $hasActive ? 'true' : 'false' }},
                                defaultOpen: {{ ($section['default_open'] ?? true) ? 'true' : 'false' }},
                                open: false,
                                init() {
                                    const stored = localStorage.getItem(this.key);
                                    if (stored === '1' || stored === '0') {
                                        this.open = stored === '1';
                                    } else {
                                        this.open = this.hasActive || this.defaultOpen;
                                    }
                                    this.$watch('open', v => localStorage.setItem(this.key, v ? '1' : '0'));
                                },
                            }"
                            class="mt-3"
                        >
                            <button
                                type="button"
                                @click="open = !open"
                                class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-content/60 hover:text-secondary-content transition-colors"
                                :aria-expanded="open"
                            >
                                <span class="flex items-center gap-2">
                                    {{ $section['label'] }}
                                    @if ($hasActive)
                                        <span class="size-1.5 rounded-full bg-accent"></span>
                                    @endif
                                </span>
                                <x-mary-icon
                                    name="o-chevron-down"
                                    class="size-3.5 transition-transform"
                                    ::class="open ? 'rotate-0' : '-rotate-90'"
                                />
                            </button>

                            <ul
                                x-show="open"
                                x-transition.duration.150ms
                                class="px-2 space-y-0.5 mt-0.5"
                            >
                                @foreach ($section['items'] as $item)
                                    <li>
                                        <a
                                            href="{{ $item['href'] }}"
                                            wire:navigate
                                            @class([
                                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                                                'bg-accent text-accent-content font-semibold shadow-sm' => $item['active'],
                                                'text-secondary-content/80 hover:bg-secondary-content/10 hover:text-secondary-content' => ! $item['active'],
                                            ])
                                        >
                                            <x-mary-icon :name="$item['icon']" class="size-5 shrink-0" />
                                            <span class="truncate">{{ $item['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        @if (! empty($section['label']))
                            <div class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-content/50">
                                {{ $section['label'] }}
                            </div>
                        @endif

                        <ul class="px-2 space-y-0.5">
                            @foreach ($section['items'] as $item)
                                <li>
                                    <a
                                        href="{{ $item['href'] }}"
                                        wire:navigate
                                        @class([
                                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                                            'bg-accent text-accent-content font-semibold shadow-sm' => $item['active'],
                                            'text-secondary-content/80 hover:bg-secondary-content/10 hover:text-secondary-content' => ! $item['active'],
                                        ])
                                    >
                                        <x-mary-icon :name="$item['icon']" class="size-5 shrink-0" />
                                        <span class="truncate">{{ $item['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
            </div>

            {{-- User --}}
            @if ($user = auth()->user())
                <div class="border-t border-secondary-content/10 p-3 space-y-2">
                    <a
                        href="{{ route('settings.index') }}"
                        wire:navigate
                        @class([
                            'flex items-center gap-3 rounded-lg p-2 transition-colors',
                            'bg-secondary-content/10' => $settingsActive,
                            'hover:bg-secondary-content/10' => ! $settingsActive,
                        ])
                    >
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent text-accent-content text-sm font-bold">
                            {{ $user->initials() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold">{{ $user->name }}</div>
                            <div class="truncate text-xs text-secondary-content/60">{{ $user->email }}</div>
                        </div>
                    </a>

                    <div class="flex gap-1">
                        <a
                            href="{{ route('settings.index') }}"
                            wire:navigate
                            class="btn btn-ghost btn-sm flex-1 justify-start text-secondary-content/80 hover:bg-secondary-content/10 hover:text-secondary-content"
                            title="{{ __('Pengaturan') }}"
                        >
                            <x-mary-icon name="o-cog-6-tooth" class="size-4" />
                            <span class="text-xs">{{ __('Pengaturan') }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="btn btn-ghost btn-sm text-accent hover:bg-accent/15"
                                title="{{ __('Keluar') }}"
                            >
                                <x-mary-icon name="o-arrow-right-on-rectangle" class="size-4" />
                                <span class="text-xs">{{ __('Keluar') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </nav>
    </aside>
</div>

<x-mary-toast />
</body>
</html>
