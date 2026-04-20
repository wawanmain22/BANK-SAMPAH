<?php

use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use App\Services\ProductSalesService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Penjualan Produk Baru')] class extends Component {
    use Toast;

    public ?int $buyer_user_id = null;

    public string $buyer_name = '';

    public string $buyer_phone = '';

    public string $payment_method = 'cash';

    public string $payment_status = 'paid';

    public string $notes = '';

    /** @var array<int, array{product_id: ?int, quantity: string, price_per_unit: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->items = [['product_id' => null, 'quantity' => '', 'price_per_unit' => '']];
    }

    #[Computed]
    public function nasabahOptions(): array
    {
        return User::nasabah()
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name.($u->phone ? ' ('.$u->phone.')' : ''),
                'phone' => $u->phone,
            ])
            ->toArray();
    }

    #[Computed]
    public function productOptions(): array
    {
        return Product::active()
            ->with('currentPrice')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name.' · '.__('stok').': '
                    .rtrim(rtrim(number_format((float) $p->stock, 3, ',', '.'), '0'), ',')
                    .' '.$p->unit,
                'stock' => (float) $p->stock,
                'unit' => $p->unit,
                'price' => (float) ($p->currentPrice?->price_per_unit ?? $p->price),
            ])
            ->toArray();
    }

    #[Computed]
    public function total(): float
    {
        return (float) collect($this->items)->sum(function ($item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price_per_unit'] ?? 0);

            return round($qty * $price, 2);
        });
    }

    #[Computed]
    public function totalQuantity(): float
    {
        return (float) collect($this->items)->sum(fn ($i) => (float) ($i['quantity'] ?? 0));
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => null, 'quantity' => '', 'price_per_unit' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function updatedItems($value, $key): void
    {
        if (str_ends_with($key, '.product_id') && $value) {
            $idx = (int) explode('.', $key)[0];
            $opt = collect($this->productOptions)->firstWhere('id', (int) $value);
            if ($opt && empty($this->items[$idx]['price_per_unit'])) {
                $this->items[$idx]['price_per_unit'] = (string) $opt['price'];
            }
        }
    }

    public function updatedBuyerUserId($value): void
    {
        if (! $value) {
            return;
        }

        $user = User::nasabah()->find($value);
        if (! $user) {
            return;
        }

        if ($this->buyer_name === '') {
            $this->buyer_name = $user->name;
        }

        if ($this->buyer_phone === '' && $user->phone) {
            $this->buyer_phone = $user->phone;
        }
    }

    public function paymentMethodOptions(): array
    {
        return [
            ['id' => 'cash', 'name' => __('Tunai')],
            ['id' => 'transfer', 'name' => __('Transfer')],
            ['id' => 'qris', 'name' => __('QRIS')],
        ];
    }

    public function paymentStatusOptions(): array
    {
        return [
            ['id' => 'paid', 'name' => __('Lunas')],
            ['id' => 'pending', 'name' => __('Belum lunas')],
        ];
    }

    public function rules(): array
    {
        return [
            'buyer_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'buyer_name' => ['required', 'string', 'max:128'],
            'buyer_phone' => ['required', 'string', 'max:32'],
            'payment_method' => ['required', 'in:'.implode(',', ProductSale::PAYMENT_METHODS)],
            'payment_status' => ['required', 'in:'.implode(',', ProductSale::PAYMENT_STATUSES)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.price_per_unit' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function save(ProductSalesService $service): void
    {
        $this->validate();

        $buyerUser = $this->buyer_user_id ? User::nasabah()->find($this->buyer_user_id) : null;

        try {
            $sale = $service->create(
                buyerName: $this->buyer_name,
                buyerPhone: $this->buyer_phone,
                items: array_map(fn ($item) => [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'price_per_unit' => (float) $item['price_per_unit'],
                ], $this->items),
                buyerUser: $buyerUser,
                paymentMethod: $this->payment_method,
                paymentStatus: $this->payment_status,
                notes: $this->notes !== '' ? $this->notes : null,
                createdBy: Auth::user(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->success(__('Penjualan #:id tersimpan.', ['id' => $sale->id]));
        $this->redirect(route('admin.product-sale.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Penjualan Produk Baru') }}"
        subtitle="{{ __('Catat penjualan produk olahan. Pembeli bisa nasabah atau walk-in.') }}"
        separator
    >
        <x-slot:actions>
            <x-mary-button label="{{ __('Batal') }}" icon="o-arrow-uturn-left" link="{{ route('admin.product-sale.index') }}" />
        </x-slot:actions>
    </x-mary-header>

    <form wire:submit="save" class="max-w-4xl space-y-6" aria-label="{{ __('Form penjualan produk baru') }}">
        <p class="text-xs text-base-content/70">
            <span aria-hidden="true" class="text-error">*</span> {{ __('Tanda bintang menandai field wajib diisi.') }}
        </p>

        {{-- Buyer --}}
        <fieldset class="space-y-3">
            <legend class="text-base font-semibold">{{ __('Data pembeli') }}</legend>

            <x-mary-select
                wire:model.live="buyer_user_id"
                label="{{ __('Pilih nasabah (opsional)') }}"
                :options="$this->nasabahOptions"
                option-label="name"
                option-value="id"
                placeholder="{{ __('Pembeli walk-in / tidak di daftar nasabah') }}"
                icon="o-user"
                hint="{{ __('Pilih kalau pembeli adalah nasabah terdaftar — nama & no HP akan auto-isi.') }}"
            />

            <div class="grid gap-3 md:grid-cols-2">
                <x-mary-input
                    wire:model="buyer_name"
                    label="{{ __('Nama pembeli') }}"
                    icon="o-identification"
                    autocomplete="name"
                    required
                />
                <x-mary-input
                    wire:model="buyer_phone"
                    label="{{ __('No HP / WhatsApp') }}"
                    icon="o-phone"
                    placeholder="08xxx"
                    type="tel"
                    inputmode="tel"
                    autocomplete="tel"
                    required
                />
            </div>
        </fieldset>

        {{-- Items --}}
        <fieldset class="space-y-3">
            <div class="flex items-center justify-between">
                <legend class="text-base font-semibold">{{ __('Produk') }}</legend>
                <x-mary-button
                    icon="o-plus"
                    label="{{ __('Tambah item') }}"
                    wire:click="addItem"
                    type="button"
                    class="btn-sm btn-ghost cursor-pointer"
                    aria-label="{{ __('Tambah baris produk baru') }}"
                />
            </div>

            @foreach ($items as $index => $item)
                @php
                    $productOpt = collect($this->productOptions)->firstWhere('id', $item['product_id']);
                    $qty = (float) ($item['quantity'] ?? 0);
                    $price = (float) ($item['price_per_unit'] ?? 0);
                    $subtotal = round($qty * $price, 2);
                @endphp
                <div wire:key="psale-{{ $index }}" class="card bg-base-100 border border-base-300">
                    <div class="card-body p-4 gap-3">
                        <div class="grid gap-3 md:grid-cols-12">
                            <div class="md:col-span-5">
                                <x-mary-select
                                    wire:model.live="items.{{ $index }}.product_id"
                                    label="{{ __('Produk') }}"
                                    :options="$this->productOptions"
                                    option-label="name"
                                    option-value="id"
                                    placeholder="{{ __('Pilih produk') }}"
                                    icon="o-cube"
                                />
                            </div>
                            <div class="md:col-span-3">
                                <x-mary-input
                                    wire:model.live="items.{{ $index }}.quantity"
                                    label="{{ __('Jumlah') }}"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    :suffix="$productOpt['unit'] ?? 'pcs'"
                                />
                            </div>
                            <div class="md:col-span-3">
                                <x-mary-input
                                    wire:model.live="items.{{ $index }}.price_per_unit"
                                    label="{{ __('Harga satuan') }}"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    prefix="Rp"
                                />
                            </div>
                            <div class="md:col-span-1 flex md:items-end">
                                <x-mary-button
                                    icon="o-trash"
                                    wire:click="removeItem({{ $index }})"
                                    type="button"
                                    class="btn-ghost btn-sm w-full text-error cursor-pointer"
                                    aria-label="{{ __('Hapus baris produk ke-:n', ['n' => $index + 1]) }}"
                                />
                            </div>
                        </div>

                        @if ($productOpt && $qty > 0)
                            <div class="text-sm flex items-center justify-between" role="status" aria-live="polite">
                                <div>
                                    <span class="text-base-content/70">{{ __('Subtotal') }}:</span>
                                    <span class="font-semibold ms-1">Rp {{ number_format($subtotal, 2, ',', '.') }}</span>
                                </div>
                                @if ($qty > $productOpt['stock'])
                                    <x-mary-badge
                                        value="{{ __('Stok tidak cukup') }}"
                                        icon="o-exclamation-triangle"
                                        class="badge-error badge-soft"
                                    />
                                @endif
                            </div>
                        @endif

                        @error("items.{$index}.product_id")
                            <p class="text-sm text-error" role="alert">{{ $message }}</p>
                        @enderror
                        @error("items.{$index}.quantity")
                            <p class="text-sm text-error" role="alert">{{ $message }}</p>
                        @enderror
                        @error("items.{$index}.price_per_unit")
                            <p class="text-sm text-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </fieldset>

        {{-- Payment --}}
        <fieldset class="space-y-3">
            <legend class="text-base font-semibold">{{ __('Pembayaran') }}</legend>

            <div class="grid gap-3 md:grid-cols-2">
                <x-mary-select
                    wire:model="payment_method"
                    label="{{ __('Metode pembayaran') }}"
                    :options="$this->paymentMethodOptions()"
                    option-label="name"
                    option-value="id"
                    icon="o-credit-card"
                    required
                />
                <x-mary-select
                    wire:model="payment_status"
                    label="{{ __('Status pembayaran') }}"
                    :options="$this->paymentStatusOptions()"
                    option-label="name"
                    option-value="id"
                    icon="o-check-badge"
                    required
                />
            </div>
        </fieldset>

        <x-mary-textarea wire:model="notes" label="{{ __('Catatan (opsional)') }}" rows="2" />

        <section
            class="rounded-xl border border-base-300 bg-base-100 p-4"
            aria-label="{{ __('Ringkasan total') }}"
            role="status"
            aria-live="polite"
        >
            <div class="flex items-center justify-between">
                <div class="text-sm text-base-content/70">{{ __('Total qty') }}</div>
                <div class="font-medium" aria-label="{{ __('Total kuantitas :n', ['n' => $this->totalQuantity]) }}">
                    {{ number_format($this->totalQuantity, 3, ',', '.') }}
                </div>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <div class="text-sm text-base-content/70">{{ __('Total harga') }}</div>
                <div
                    class="text-xl font-bold text-primary"
                    aria-label="{{ __('Total harga :amt rupiah', ['amt' => number_format($this->total, 0, ',', '.')]) }}"
                >
                    Rp {{ number_format($this->total, 2, ',', '.') }}
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-2">
            <x-mary-button label="{{ __('Batal') }}" link="{{ route('admin.product-sale.index') }}" />
            <x-mary-button
                type="submit"
                label="{{ __('Simpan Penjualan') }}"
                class="btn-primary"
                spinner="save"
                data-test="product-sale-save-button"
            />
        </div>
    </form>
</section>
