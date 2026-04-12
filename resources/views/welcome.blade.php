@php
    use App\Models\Article;
    use App\Models\Product;
    use App\Models\SavingTransaction;
    use App\Models\User;

    $nasabahCount = User::nasabah()->count();
    $totalWeight = (float) SavingTransaction::sum('total_weight');
    $latestArticles = Article::published()->with('author:id,name')->orderByDesc('published_at')->limit(3)->get();
    $featuredProducts = Product::active()->where('stock', '>', 0)->orderBy('name')->limit(4)->get();
@endphp

<x-layouts::public :title="__('Selamat Datang')">
    {{-- Hero --}}
    <section class="bg-gradient-to-b from-primary/10 to-transparent">
        <div class="mx-auto max-w-6xl px-4 py-16 md:py-24 text-center">
            <div class="mx-auto mb-6 flex aspect-square size-16 items-center justify-center rounded-xl bg-primary text-primary-content">
                <x-app-logo-icon class="size-10 fill-current" />
            </div>
            <h1 class="text-3xl md:text-5xl font-bold tracking-tight">
                {{ __('Bank Sampah Pak Toni') }}
            </h1>
            <p class="mx-auto mt-4 max-w-xl text-base-content/70">
                {{ __('Sistem operasional pengumpulan dan daur ulang sampah masyarakat. Tabung sampah, dapatkan saldo & poin, atau sedekahkan untuk bumi yang lebih baik.') }}
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <x-mary-button link="{{ route('public.edukasi.index') }}" label="{{ __('Baca Edukasi') }}" icon="o-book-open" class="btn-primary" />
                <x-mary-button link="{{ route('public.merchandise.index') }}" label="{{ __('Lihat Produk') }}" icon="o-cube" class="btn-ghost" />
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section>
        <div class="mx-auto max-w-6xl px-4 py-10">
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
                <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-base-300">
                    <div class="text-center sm:pe-4">
                        <div class="text-3xl font-bold text-primary">{{ number_format($nasabahCount) }}</div>
                        <div class="text-sm text-base-content/60 mt-1">{{ __('Nasabah terdaftar') }}</div>
                    </div>
                    <div class="text-center sm:px-4 pt-4 sm:pt-0">
                        <div class="text-3xl font-bold text-primary">
                            {{ rtrim(rtrim(number_format($totalWeight, 3, ',', '.'), '0'), ',') }} kg
                        </div>
                        <div class="text-sm text-base-content/60 mt-1">{{ __('Total sampah tertabung') }}</div>
                    </div>
                    <div class="text-center sm:ps-4 pt-4 sm:pt-0">
                        <div class="text-3xl font-bold text-primary">{{ $latestArticles->count() + $featuredProducts->count() }}</div>
                        <div class="text-sm text-base-content/60 mt-1">{{ __('Konten edukasi & produk') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Latest articles --}}
    @if ($latestArticles->isNotEmpty())
        <section>
            <div class="mx-auto max-w-6xl px-4 py-12">
                <div class="flex items-end justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold">{{ __('Edukasi Terbaru') }}</h2>
                        <p class="text-sm text-base-content/60">{{ __('Belajar tentang daur ulang & dampak lingkungan.') }}</p>
                    </div>
                    <a href="{{ route('public.edukasi.index') }}" wire:navigate class="link link-primary text-sm hidden md:inline">
                        {{ __('Lihat semua') }} →
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach ($latestArticles as $article)
                        <a href="{{ route('public.edukasi.show', $article) }}" wire:navigate class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-lg transition-shadow overflow-hidden rounded-2xl">
                            @if ($article->featured_image)
                                <figure class="aspect-video">
                                    <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="w-full h-full object-cover" />
                                </figure>
                            @endif
                            <div class="card-body">
                                <h3 class="card-title text-base">{{ $article->title }}</h3>
                                @if ($article->excerpt)
                                    <p class="text-sm text-base-content/70 line-clamp-3">{{ $article->excerpt }}</p>
                                @endif
                                <div class="text-xs text-base-content/50 mt-2">
                                    {{ $article->published_at->format('d M Y') }}
                                    @if ($article->author) • {{ $article->author->name }} @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Featured products --}}
    @if ($featuredProducts->isNotEmpty())
        <section class="border-t border-base-300">
            <div class="mx-auto max-w-6xl px-4 py-12">
                <div class="flex items-end justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold">{{ __('Produk Unggulan') }}</h2>
                        <p class="text-sm text-base-content/60">{{ __('Hasil olahan sampah yang bisa Anda tukar dengan poin atau beli.') }}</p>
                    </div>
                    <a href="{{ route('public.merchandise.index') }}" wire:navigate class="link link-primary text-sm hidden md:inline">
                        {{ __('Lihat semua') }} →
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach ($featuredProducts as $product)
                        <div class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-lg transition-shadow overflow-hidden rounded-2xl">
                            <figure class="aspect-square bg-primary/5">
                                @if ($product->image)
                                    <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-full object-cover" />
                                @else
                                    <div class="flex w-full h-full items-center justify-center text-primary">
                                        <x-mary-icon name="o-cube" class="size-12" />
                                    </div>
                                @endif
                            </figure>
                            <div class="card-body p-4">
                                <h3 class="font-semibold text-sm">{{ $product->name }}</h3>
                                <div class="text-sm text-primary font-semibold">
                                    Rp {{ number_format((float) $product->price, 0, ',', '.') }}
                                </div>
                                <div class="text-xs text-base-content/60">
                                    {{ __('Stok') }}:
                                    {{ rtrim(rtrim(number_format((float) $product->stock, 3, ',', '.'), '0'), ',') }}
                                    {{ $product->unit }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- How it works --}}
    <section>
        <div class="mx-auto max-w-6xl px-4 py-12">
            <h2 class="text-2xl font-bold text-center mb-8">{{ __('Cara Kerja') }}</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
                    <div class="card-body items-center text-center">
                        <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary mb-2">
                            <x-mary-icon name="o-scale" class="size-6" />
                        </div>
                        <h3 class="card-title text-lg">1. {{ __('Timbang & Catat') }}</h3>
                        <p class="text-sm text-base-content/70">{{ __('Bawa sampah Anda ke Pak Toni. Admin akan timbang dan catat ke sistem sesuai harga berlaku.') }}</p>
                    </div>
                </div>
                <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
                    <div class="card-body items-center text-center">
                        <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary mb-2">
                            <x-mary-icon name="o-banknotes" class="size-6" />
                        </div>
                        <h3 class="card-title text-lg">2. {{ __('Saldo Terkumpul') }}</h3>
                        <p class="text-sm text-base-content/70">{{ __('Nilai sampah jadi saldo Anda. Member juga dapat poin untuk ditukar merchandise.') }}</p>
                    </div>
                </div>
                <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
                    <div class="card-body items-center text-center">
                        <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary mb-2">
                            <x-mary-icon name="o-wallet" class="size-6" />
                        </div>
                        <h3 class="card-title text-lg">3. {{ __('Cairkan') }}</h3>
                        <p class="text-sm text-base-content/70">{{ __('Setelah dana siap, admin rilis saldo dan Anda bisa cairkan via cash atau transfer.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts::public>
