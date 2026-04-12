<?php

use App\Models\Partner;
use App\Models\WasteCategory;
use App\Services\SalesTransactionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Penjualan Baru')] class extends Component {
    use Toast;

    public ?int $partner_id = null;

    public string $notes = '';

    /** @var array<int, array{waste_category_id: ?int, quantity: string, price_per_unit: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->items = [['waste_category_id' => null, 'quantity' => '', 'price_per_unit' => '']];
    }

    #[Computed]
    public function partnerOptions(): array
    {
        return Partner::active()
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name.' ('.ucfirst($p->type).')'])
            ->toArray();
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return WasteCategory::active()
            ->with(['inventory', 'currentPrice'])
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name.' — '.__('stok').': '
                    .rtrim(rtrim(number_format((float) ($c->inventory?->stock ?? 0), 3, ',', '.'), '0'), ',')
                    .' '.$c->unit,
                'stock' => (float) ($c->inventory?->stock ?? 0),
                'unit' => $c->unit,
                'suggested_price' => $c->currentPrice?->price_per_unit,
            ])
            ->toArray();
    }

    #[Computed]
    public function total(): float
    {
        return collect($this->items)->sum(function ($item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price_per_unit'] ?? 0);

            return round($qty * $price, 2);
        });
    }

    #[Computed]
    public function totalWeight(): float
    {
        return (float) collect($this->items)->sum(fn ($i) => (float) ($i['quantity'] ?? 0));
    }

    public function addItem(): void
    {
        $this->items[] = ['waste_category_id' => null, 'quantity' => '', 'price_per_unit' => ''];
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
        // Auto-fill suggested price when category chosen
        if (str_ends_with($key, '.waste_category_id') && $value) {
            $idx = (int) explode('.', $key)[0];
            $opt = collect($this->categoryOptions)->firstWhere('id', (int) $value);
            if ($opt && $opt['suggested_price'] && empty($this->items[$idx]['price_per_unit'])) {
                $this->items[$idx]['price_per_unit'] = (string) $opt['suggested_price'];
            }
        }
    }

    public function rules(): array
    {
        return [
            'partner_id' => ['required', 'integer', 'exists:partners,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.waste_category_id' => ['required', 'integer', 'exists:waste_categories,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.price_per_unit' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function save(SalesTransactionService $service): void
    {
        $this->validate();

        $partner = Partner::active()->findOrFail($this->partner_id);

        try {
            $transaction = $service->create(
                partner: $partner,
                items: array_map(fn ($item) => [
                    'waste_category_id' => (int) $item['waste_category_id'],
                    'quantity' => (float) $item['quantity'],
                    'price_per_unit' => (float) $item['price_per_unit'],
                ], $this->items),
                notes: $this->notes !== '' ? $this->notes : null,
                createdBy: Auth::user(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->success(__('Penjualan #:id tersimpan.', ['id' => $transaction->id]));
        $this->redirect(route('admin.sales.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Penjualan Baru') }}"
        subtitle="{{ __('Pilih mitra dan input item sampah yang dijual. Harga jual bisa berbeda dari harga pasar.') }}"
        separator
    >
        <x-slot:actions>
            <x-mary-button label="{{ __('Batal') }}" icon="o-arrow-uturn-left" link="{{ route('admin.sales.index') }}" />
        </x-slot:actions>
    </x-mary-header>

    <form wire:submit="save" class="max-w-4xl space-y-6">
        <x-mary-select
            wire:model="partner_id"
            label="{{ __('Mitra') }}"
            :options="$this->partnerOptions"
            option-label="name"
            option-value="id"
            placeholder="{{ __('Pilih mitra') }}"
            icon="o-building-office"
            required
        />

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold">{{ __('Item penjualan') }}</h3>
                <x-mary-button
                    icon="o-plus"
                    label="{{ __('Tambah item') }}"
                    wire:click="addItem"
                    type="button"
                    class="btn-sm btn-ghost"
                />
            </div>

            @foreach ($items as $index => $item)
                @php
                    $categoryOpt = collect($this->categoryOptions)->firstWhere('id', $item['waste_category_id']);
                    $qty = (float) ($item['quantity'] ?? 0);
                    $price = (float) ($item['price_per_unit'] ?? 0);
                    $subtotal = round($qty * $price, 2);
                @endphp
                <div wire:key="salei-{{ $index }}" class="card bg-base-100 border border-base-300">
                    <div class="card-body p-4 gap-3">
                        <div class="grid gap-3 md:grid-cols-12">
                            <div class="md:col-span-5">
                                <x-mary-select
                                    wire:model.live="items.{{ $index }}.waste_category_id"
                                    label="{{ __('Kategori') }}"
                                    :options="$this->categoryOptions"
                                    option-label="name"
                                    option-value="id"
                                    placeholder="{{ __('Pilih kategori') }}"
                                    icon="o-tag"
                                />
                            </div>
                            <div class="md:col-span-3">
                                <x-mary-input
                                    wire:model.live="items.{{ $index }}.quantity"
                                    label="{{ __('Berat') }}"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    :suffix="$categoryOpt['unit'] ?? 'kg'"
                                />
                            </div>
                            <div class="md:col-span-3">
                                <x-mary-input
                                    wire:model.live="items.{{ $index }}.price_per_unit"
                                    label="{{ __('Harga jual') }}"
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
                                    class="btn-ghost btn-sm w-full text-error"
                                />
                            </div>
                        </div>

                        @if ($categoryOpt && $qty > 0)
                            <div class="text-sm flex items-center justify-between">
                                <div>
                                    <span class="text-base-content/60">{{ __('Subtotal') }}:</span>
                                    <span class="font-semibold ms-1">Rp {{ number_format($subtotal, 2, ',', '.') }}</span>
                                </div>
                                @if ($qty > $categoryOpt['stock'])
                                    <x-mary-badge value="{{ __('Stok tidak cukup') }}" class="badge-error badge-soft" />
                                @endif
                            </div>
                        @endif

                        @error("items.{$index}.waste_category_id")
                            <div class="text-sm text-error">{{ $message }}</div>
                        @enderror
                        @error("items.{$index}.quantity")
                            <div class="text-sm text-error">{{ $message }}</div>
                        @enderror
                        @error("items.{$index}.price_per_unit")
                            <div class="text-sm text-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <x-mary-textarea wire:model="notes" label="{{ __('Catatan (opsional)') }}" rows="2" />

        <div class="rounded-xl border border-base-300 bg-base-100 p-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-base-content/70">{{ __('Total berat') }}</div>
                <div class="font-medium">{{ number_format($this->totalWeight, 3, ',', '.') }} kg</div>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <div class="text-sm text-base-content/70">{{ __('Total nilai') }}</div>
                <div class="text-xl font-bold text-primary">
                    Rp {{ number_format($this->total, 2, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <x-mary-button label="{{ __('Batal') }}" link="{{ route('admin.sales.index') }}" />
            <x-mary-button
                type="submit"
                label="{{ __('Simpan Penjualan') }}"
                class="btn-primary"
                spinner="save"
                data-test="sales-save-button"
            />
        </div>
    </form>
</section>
