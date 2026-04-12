<x-layouts::public :title="$article->title">
    <article class="mx-auto max-w-3xl px-4 py-10">
        <a href="{{ route('public.edukasi.index') }}" wire:navigate class="link link-primary text-sm inline-flex items-center gap-1 mb-4">
            ← {{ __('Kembali ke edukasi') }}
        </a>

        @if ($article->featured_image)
            <figure class="mb-6 overflow-hidden rounded-xl border border-base-300">
                <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="w-full aspect-video object-cover" />
            </figure>
        @endif

        <header class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold leading-tight">{{ $article->title }}</h1>
            <div class="text-sm text-base-content/60 mt-3">
                {{ $article->published_at->format('d F Y') }}
                @if ($article->author) • {{ __('oleh') }} {{ $article->author->name }} @endif
            </div>
            @if ($article->excerpt)
                <p class="text-lg text-base-content/80 mt-4 italic">{{ $article->excerpt }}</p>
            @endif
        </header>

        <div class="prose prose-neutral max-w-none text-base-content [&_p]:mb-4 whitespace-pre-wrap">{!! e($article->content) !!}</div>

        @if (! empty($article->images))
            <section class="mt-12">
                <h2 class="text-lg font-semibold mb-4">{{ __('Galeri') }}</h2>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                    @foreach ($article->images as $img)
                        <figure class="overflow-hidden rounded-lg border border-base-300">
                            <img src="{{ $img }}" alt="{{ $article->title }}" class="w-full aspect-square object-cover hover:scale-105 transition-transform" />
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif
    </article>
</x-layouts::public>
