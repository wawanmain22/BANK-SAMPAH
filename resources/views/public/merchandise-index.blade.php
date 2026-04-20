@php
    use App\Models\Product;

    $products = Product::active()->orderBy('name')->paginate(12);
    $waNumber = preg_replace('/\D/', '', (string) config('banksampah.admin_whatsapp'));
@endphp

<x-layouts::public :title="__('Merchandise')">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <header class="mb-8">
            <h1 class="text-3xl font-bold">{{ __('Merchandise') }}</h1>
            <p class="text-base-content/60 mt-2">{{ __('Produk hasil olahan sampah — bisa dibeli atau ditukar dengan poin. Klik "Pesan via WhatsApp" untuk tanya stok ke admin.') }}</p>
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
                    @php
                        $priceLabel = 'Rp '.number_format((float) $product->price, 0, ',', '.');
                        $stockLabel = rtrim(rtrim(number_format((float) $product->stock, 3, ',', '.'), '0'), ',').' '.$product->unit;

                        $message = "Halo Admin Bank Sampah 👋\n\n"
                            ."Saya tertarik dengan produk *{$product->name}* (harga {$priceLabel} / {$product->unit}).\n\n"
                            ."Apakah stok masih tersedia? Terima kasih.";

                        $waUrl = 'https://wa.me/'.$waNumber.'?text='.rawurlencode($message);
                    @endphp
                    <article class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-lg transition-shadow overflow-hidden rounded-2xl flex flex-col">
                        <figure class="aspect-square bg-primary/5">
                            @if ($product->image)
                                <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-full object-cover" loading="lazy" />
                            @else
                                <div class="flex w-full h-full items-center justify-center text-primary">
                                    <x-mary-icon name="o-cube" class="size-14" />
                                </div>
                            @endif
                        </figure>
                        <div class="card-body p-4 gap-1 flex-1">
                            <h2 class="font-semibold text-base line-clamp-1">{{ $product->name }}</h2>
                            @if ($product->description)
                                <p class="text-xs text-base-content/60 line-clamp-2">{{ $product->description }}</p>
                            @endif
                            <div class="text-lg text-primary font-bold mt-2">
                                {{ $priceLabel }}
                                <span class="text-xs font-normal text-base-content/60">/ {{ $product->unit }}</span>
                            </div>
                            <div class="text-xs text-base-content/60">
                                {{ __('Stok') }}: {{ $stockLabel }}
                            </div>

                            <a
                                href="{{ $waUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-success btn-sm w-full mt-3 text-white"
                                aria-label="{{ __('Pesan :name via WhatsApp', ['name' => $product->name]) }}"
                                data-test="wa-order-{{ $product->id }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.889-9.884 2.64 0 5.122 1.03 6.988 2.898a9.83 9.83 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.82 11.82 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.9 11.9 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.82 11.82 0 0 0-3.48-8.413Z" />
                                </svg>
                                <span>{{ __('Pesan via WhatsApp') }}</span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</x-layouts::public>
