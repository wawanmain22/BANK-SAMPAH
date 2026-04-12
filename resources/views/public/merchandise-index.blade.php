@php
    $products = App\Models\Product::active()->orderBy('name')->paginate(12);
@endphp

<x-layouts::public :title="__('Merchandise')">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <header class="mb-8">
            <h1 class="text-3xl font-bold">{{ __('Merchandise') }}</h1>
            <p class="text-base-content/60 mt-2">{{ __('Produk hasil olahan sampah — bisa dibeli atau ditukar dengan poin.') }}</p>
        </header>

        @if ($products->isEmpty())
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl">
                <div class="card-body text-center text-base-content/60 py-16">
                    {{ __('Belum ada produk tersedia.') }}
                </div>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <div class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-lg transition-shadow overflow-hidden rounded-2xl">
                        <figure class="aspect-square bg-primary/5">
                            @if ($product->image)
                                <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-full object-cover" />
                            @else
                                <div class="flex w-full h-full items-center justify-center text-primary">
                                    <x-mary-icon name="o-cube" class="size-14" />
                                </div>
                            @endif
                        </figure>
                        <div class="card-body p-4">
                            <h3 class="font-semibold">{{ $product->name }}</h3>
                            @if ($product->description)
                                <p class="text-xs text-base-content/60 line-clamp-2">{{ $product->description }}</p>
                            @endif
                            <div class="text-lg text-primary font-bold mt-2">
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

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</x-layouts::public>
