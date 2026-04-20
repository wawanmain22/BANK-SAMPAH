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

    {{-- Inline — avoids FOUC before Alpine evaluates on hard refresh. --}}
    <style>
        [x-cloak] { display: none !important; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200 text-base-content">

@php
    $isStaff = auth()->user()?->isAdmin() || auth()->user()?->isOwner();

    $dashboardHref = $isStaff ? route('admin.dashboard') : route('dashboard');
    $dashboardMatch = $isStaff ? '/admin' : '/dashboard';
    // Dashboard always exact — otherwise '/admin' prefix would match every
    // '/admin/*' page and highlight together with the current sub-menu.
    $dashboardExact = true;

    $navSections = [
        [
            'items' => [
                ['label' => __('Dashboard'), 'icon' => 'o-home', 'href' => $dashboardHref, 'match' => $dashboardMatch, 'exact' => $dashboardExact],
            ],
        ],
    ];

    if (! $isStaff && auth()->user()?->isNasabah()) {
        $navSections[] = [
            'label' => __('Akun Saya'),
            'items' => [
                ['label' => __('Saldo'), 'icon' => 'o-banknotes', 'href' => route('nasabah.saldo'), 'match' => '/saldo', 'exact' => true],
                ['label' => __('Transaksi Nabung'), 'icon' => 'o-arrow-trending-up', 'href' => route('nasabah.transaksi'), 'match' => '/transaksi', 'exact' => true],
                ['label' => __('Pencairan'), 'icon' => 'o-wallet', 'href' => route('nasabah.pencairan'), 'match' => '/pencairan-saya', 'exact' => true],
                ['label' => __('Histori Poin'), 'icon' => 'o-sparkles', 'href' => route('nasabah.poin'), 'match' => '/poin', 'exact' => true],
            ],
        ];
    }

    if ($isStaff) {
        $staffGroups = [
            [
                'label' => __('Manajemen'), 'key' => 'manajemen', 'default_open' => false,
                'items' => [
                    ['label' => __('Nasabah'), 'icon' => 'o-users', 'href' => route('admin.nasabah.index'), 'match' => '/admin/nasabah', 'exact' => false],
                ],
            ],
            [
                'label' => __('Master Data'), 'key' => 'master', 'default_open' => true,
                'items' => [
                    ['label' => __('Kategori Sampah'), 'icon' => 'o-tag', 'href' => route('admin.waste-category.index'), 'match' => '/admin/kategori-sampah', 'exact' => false],
                    ['label' => __('Barang Sampah'), 'icon' => 'o-hashtag', 'href' => route('admin.waste-item.index'), 'match' => '/admin/barang-sampah', 'exact' => false],
                    ['label' => __('Mitra'), 'icon' => 'o-building-office', 'href' => route('admin.partner.index'), 'match' => '/admin/mitra', 'exact' => false],
                    ['label' => __('Produk'), 'icon' => 'o-cube', 'href' => route('admin.product.index'), 'match' => '/admin/produk', 'exact' => false],
                    ['label' => __('Master Poin'), 'icon' => 'o-star', 'href' => route('admin.point-rule.index'), 'match' => '/admin/master-poin', 'exact' => false],
                ],
            ],
            [
                'label' => __('Transaksi'), 'key' => 'transaksi', 'default_open' => true,
                'items' => [
                    ['label' => __('Nabung'), 'icon' => 'o-arrow-trending-up', 'href' => route('admin.saving.index'), 'match' => '/admin/nabung', 'exact' => false],
                    ['label' => __('Sedekah'), 'icon' => 'o-heart', 'href' => route('admin.sedekah.index'), 'match' => '/admin/sedekah', 'exact' => false],
                    ['label' => __('Penjualan ke Mitra'), 'icon' => 'o-truck', 'href' => route('admin.sales.index'), 'match' => '/admin/penjualan', 'exact' => false],
                    ['label' => __('Pengolahan'), 'icon' => 'o-cog-8-tooth', 'href' => route('admin.processing.index'), 'match' => '/admin/pengolahan', 'exact' => false],
                    ['label' => __('Penjualan Produk'), 'icon' => 'o-shopping-bag', 'href' => route('admin.product-sale.index'), 'match' => '/admin/penjualan-produk', 'exact' => false],
                ],
            ],
            [
                'label' => __('Inventory'), 'key' => 'inventory', 'default_open' => true,
                'items' => [
                    ['label' => __('Inventory Nabung'), 'icon' => 'o-archive-box', 'href' => route('admin.inventory.index', ['source' => 'nabung']), 'match' => '/admin/inventory', 'query' => 'source', 'query_value' => 'nabung'],
                    ['label' => __('Inventory Sedekah'), 'icon' => 'o-gift', 'href' => route('admin.inventory.index', ['source' => 'sedekah']), 'match' => '/admin/inventory', 'query' => 'source', 'query_value' => 'sedekah'],
                ],
            ],
            [
                'label' => __('Keuangan'), 'key' => 'keuangan', 'default_open' => true,
                'items' => [
                    ['label' => __('Release Saldo'), 'icon' => 'o-arrow-right-circle', 'href' => route('admin.release.index'), 'match' => '/admin/release-saldo', 'exact' => false],
                    ['label' => __('Pencairan'), 'icon' => 'o-wallet', 'href' => route('admin.withdrawal.index'), 'match' => '/admin/pencairan', 'exact' => false],
                ],
            ],
            [
                'label' => __('Loyalti'), 'key' => 'loyalti', 'default_open' => false,
                'items' => [
                    ['label' => __('Tukar Poin → Produk'), 'icon' => 'o-gift', 'href' => route('admin.redemption.index'), 'match' => '/admin/tukar-poin', 'exact' => true],
                    ['label' => __('Tukar Poin → Saldo'), 'icon' => 'o-banknotes', 'href' => route('admin.point-cash-out.index'), 'match' => '/admin/tukar-poin-saldo', 'exact' => false],
                    ['label' => __('Histori Poin'), 'icon' => 'o-sparkles', 'href' => route('admin.point-history.index'), 'match' => '/admin/histori-poin', 'exact' => false],
                ],
            ],
            [
                'label' => __('Konten'), 'key' => 'konten', 'default_open' => false,
                'items' => [
                    ['label' => __('Edukasi'), 'icon' => 'o-book-open', 'href' => route('admin.article.index'), 'match' => '/admin/artikel', 'exact' => false],
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

    /*
     * Server-side active state for first-paint. Alpine takes over on
     * `livewire:navigated` so highlight stays correct after client nav.
     */
    $currentPath = '/'.ltrim(request()->path(), '/');
    $currentQuery = request()->query();

    $isItemActive = function (array $item) use ($currentPath, $currentQuery) {
        if (isset($item['query'])) {
            if ($currentPath !== $item['match']) return false;
            return (string) ($currentQuery[$item['query']] ?? '') === (string) $item['query_value'];
        }
        if ($item['exact'] ?? false) return $currentPath === $item['match'];
        return $currentPath === $item['match'] || str_starts_with($currentPath, $item['match'].'/');
    };
@endphp

{{-- Mobile top bar (not persisted — tiny, re-renders per page). --}}
<header class="lg:hidden sticky top-0 z-30 flex items-center justify-between bg-secondary text-secondary-content px-4 py-3 shadow-sm">
    <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
        <div class="flex aspect-square size-9 items-center justify-center rounded-md bg-primary text-primary-content">
            <x-app-logo-icon class="size-5 fill-current" />
        </div>
        <div class="leading-tight">
            <div class="text-sm font-bold uppercase tracking-wide">{{ config('app.name', 'Bank Sampah') }}</div>
        </div>
    </a>
    <button
        type="button"
        @click="$dispatch('toggle-sidebar')"
        class="btn btn-square btn-ghost btn-sm text-secondary-content hover:bg-secondary-content/10"
        aria-label="{{ __('Buka menu') }}"
    >
        <x-mary-icon name="o-bars-3" />
    </button>
</header>

{{-- Persisted sidebar — DOM preserved across wire:navigate. --}}
@persist('app-sidebar')
<div
    x-data="{
        mobileOpen: false,
        path: window.location.pathname,
        query: new URLSearchParams(window.location.search),
        isActive(item) {
            if (item.query) {
                if (this.path !== item.match) return false;
                return (this.query.get(item.query) ?? '') === item.query_value;
            }
            if (item.exact) return this.path === item.match;
            return this.path === item.match || this.path.startsWith(item.match + '/');
        },
        syncLocation() {
            this.path = window.location.pathname;
            this.query = new URLSearchParams(window.location.search);
        },
    }"
    @toggle-sidebar.window="mobileOpen = !mobileOpen"
    @keydown.escape.window="mobileOpen = false"
    x-init="document.addEventListener('livewire:navigated', () => { syncLocation(); mobileOpen = false; })"
>
    {{-- Backdrop (mobile only) — x-cloak prevents flash of visible overlay before Alpine mounts. --}}
    <div
        x-show="mobileOpen"
        x-transition.opacity
        x-cloak
        @click="mobileOpen = false"
        class="fixed inset-0 bg-black/40 z-30 lg:hidden"
        aria-hidden="true"
    ></div>

    {{-- Default classes bake in `-translate-x-full lg:translate-x-0` so first
         paint (before Alpine mounts) shows correct off-screen mobile / visible
         desktop state. Alpine toggles `!translate-x-0` only on mobileOpen. --}}
    <aside
        :class="mobileOpen ? '!translate-x-0' : ''"
        class="fixed inset-y-0 left-0 z-40 w-64 bg-secondary text-secondary-content transform transition-transform duration-200 ease-out -translate-x-full lg:translate-x-0"
    >
        <nav class="flex h-screen w-64 flex-col">
            {{-- Brand --}}
            <div class="flex h-16 items-center justify-between gap-2 px-4 border-b border-secondary-content/10 shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2 min-w-0" wire:navigate>
                    <div class="flex aspect-square size-9 items-center justify-center rounded-md bg-primary text-primary-content shrink-0">
                        <x-app-logo-icon class="size-5 fill-current" />
                    </div>
                    <div class="leading-tight min-w-0">
                        <div class="text-sm font-bold uppercase tracking-wide truncate">{{ config('app.name', 'Bank Sampah') }}</div>
                        <div class="text-[10px] uppercase tracking-[0.2em] text-secondary-content/60">Eco Operational</div>
                    </div>
                </a>
                <a href="{{ route('home') }}" class="btn btn-square btn-ghost btn-sm shrink-0 text-secondary-content hover:bg-secondary-content/10" title="{{ __('Ke Beranda Situs') }}">
                    <x-mary-icon name="o-home" class="size-5" />
                </a>
            </div>

            {{-- Menu. Hard refresh starts at top. wire:navigate preserves scroll via
                 in-memory snapshot (Livewire zeroes internal scroll on navigation). --}}
            <div
                id="nav-scroll-root"
                class="flex-1 overflow-y-auto py-3"
                x-data="{
                    init() {
                        const el = this.$el;
                        document.addEventListener('click', (e) => {
                            const link = e.target.closest && e.target.closest('a[wire\\:navigate]');
                            if (link) window.__sidebarScroll = el.scrollTop;
                        }, true);
                        document.addEventListener('livewire:navigated', () => {
                            const s = window.__sidebarScroll;
                            if (typeof s === 'number' && s > 0) {
                                requestAnimationFrame(() => { el.scrollTop = s; });
                            }
                        });
                    },
                }"
            >
                @foreach ($navSections as $section)
                    @php
                        $collapsible = $section['collapsible'] ?? false;
                        $sectionKey = $section['key'] ?? ($section['label'] ?? 'nav');
                        $serverHasActive = collect($section['items'] ?? [])->contains(fn ($i) => $isItemActive($i));
                        $serverOpen = $serverHasActive || ($section['default_open'] ?? true);
                    @endphp

                    @if ($collapsible && ! empty($section['label']))
                        <div
                            x-data="{
                                key: 'nav:{{ $sectionKey }}',
                                defaultOpen: {{ ($section['default_open'] ?? true) ? 'true' : 'false' }},
                                items: {{ json_encode($section['items']) }},
                                open: {{ $serverOpen ? 'true' : 'false' }},
                                hasActive() { return this.items.some(i => isActive(i)); },
                                init() {
                                    const stored = localStorage.getItem(this.key);
                                    if (stored === '1' || stored === '0') {
                                        this.open = stored === '1';
                                    } else {
                                        this.open = this.hasActive() || this.defaultOpen;
                                    }
                                    this.$watch('open', v => localStorage.setItem(this.key, v ? '1' : '0'));
                                },
                            }"
                            class="mt-3"
                        >
                            <button
                                type="button"
                                @click="open = !open"
                                class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-content/60 hover:text-secondary-content transition-colors cursor-pointer"
                                :aria-expanded="open"
                            >
                                <span class="flex items-center gap-2">
                                    {{ $section['label'] }}
                                    <span
                                        x-show="hasActive()"
                                        class="size-1.5 rounded-full bg-accent"
                                        @if (! $serverHasActive) style="display:none" @endif
                                    ></span>
                                </span>
                                <x-mary-icon name="o-chevron-down" class="size-3.5 transition-transform" ::class="open ? 'rotate-0' : '-rotate-90'" />
                            </button>

                            <ul
                                x-show="open"
                                x-transition.duration.150ms
                                @if (! $serverOpen) style="display:none" @endif
                                class="px-2 space-y-0.5 mt-0.5"
                            >
                                @foreach ($section['items'] as $item)
                                    @php $itemActive = $isItemActive($item); @endphp
                                    <li>
                                        <a
                                            href="{{ $item['href'] }}"
                                            wire:navigate
                                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors {{ $itemActive ? 'bg-accent text-accent-content font-semibold shadow-sm' : 'text-secondary-content/80 hover:bg-secondary-content/10 hover:text-secondary-content' }}"
                                            :class="{
                                                'bg-accent text-accent-content font-semibold shadow-sm': isActive({{ json_encode($item) }}),
                                                'text-secondary-content/80 hover:bg-secondary-content/10 hover:text-secondary-content': !isActive({{ json_encode($item) }}),
                                            }"
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
                                @php $itemActive = $isItemActive($item); @endphp
                                <li>
                                    <a
                                        href="{{ $item['href'] }}"
                                        wire:navigate
                                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors {{ $itemActive ? 'bg-accent text-accent-content font-semibold shadow-sm' : 'text-secondary-content/80 hover:bg-secondary-content/10 hover:text-secondary-content' }}"
                                        :class="{
                                            'bg-accent text-accent-content font-semibold shadow-sm': isActive({{ json_encode($item) }}),
                                            'text-secondary-content/80 hover:bg-secondary-content/10 hover:text-secondary-content': !isActive({{ json_encode($item) }}),
                                        }"
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
                @php
                    $settingsActive = request()->routeIs('settings.index') || request()->routeIs('profile.edit') || request()->routeIs('security.edit') || request()->routeIs('appearance.edit');
                @endphp
                <div class="border-t border-secondary-content/10 p-3 space-y-2 shrink-0">
                    <a
                        href="{{ route('settings.index') }}"
                        wire:navigate
                        class="flex items-center gap-3 rounded-lg p-2 transition-colors"
                        :class="{
                            'bg-secondary-content/10': path.startsWith('/settings'),
                            'hover:bg-secondary-content/10': !path.startsWith('/settings'),
                        }"
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
                        <a href="{{ route('settings.index') }}" wire:navigate class="btn btn-ghost btn-sm flex-1 justify-start text-secondary-content/80 hover:bg-secondary-content/10 hover:text-secondary-content" title="{{ __('Pengaturan') }}">
                            <x-mary-icon name="o-cog-6-tooth" class="size-4" />
                            <span class="text-xs">{{ __('Pengaturan') }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm text-accent hover:bg-accent/15" title="{{ __('Keluar') }}">
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
@endpersist

{{-- Main content — swapped by wire:navigate. --}}
<main class="lg:pl-64 min-h-screen">
    <div class="mx-auto w-full max-w-7xl p-4 md:p-6 lg:p-8">
        {{ $slot }}
    </div>
</main>

<x-mary-toast />
</body>
</html>
