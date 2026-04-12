@php
    $articles = App\Models\Article::published()
        ->with('author:id,name')
        ->orderByDesc('published_at')
        ->paginate(12);
@endphp

<x-layouts::public :title="__('Edukasi')">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <header class="mb-8">
            <h1 class="text-3xl font-bold">{{ __('Edukasi') }}</h1>
            <p class="text-base-content/60 mt-2">{{ __('Kumpulan artikel tentang pengelolaan sampah, daur ulang, dan gaya hidup berkelanjutan.') }}</p>
        </header>

        @if ($articles->isEmpty())
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
                <div class="card-body text-center text-base-content/60 py-16">
                    {{ __('Belum ada artikel yang terbit.') }}
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <a href="{{ route('public.edukasi.show', $article) }}" wire:navigate class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-lg transition-shadow overflow-hidden rounded-2xl">
                        @if ($article->featured_image)
                            <figure class="aspect-video">
                                <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" class="w-full h-full object-cover" />
                            </figure>
                        @endif
                        <div class="card-body">
                            <h2 class="card-title text-base">{{ $article->title }}</h2>
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

            <div class="mt-8">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</x-layouts::public>
