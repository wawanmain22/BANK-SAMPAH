<?php

use App\Models\Product;
use App\Models\WasteCategory;
use App\Services\ProcessingTransactionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Pengolahan Baru')] class extends Component {
    use Toast;

    public string $notes = '';

    /** @var array<int, array{waste_category_id: ?int, quantity: string}> */
    public array $inputs = [];

    /** @var array<int, array{product_id: ?int, quantity: string}> */
    public array $outputs = [];

    public function mount(): void
    {
        $this->inputs = [['waste_category_id' => null, 'quantity' => '']];
        $this->outputs = [];
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return WasteCategory::active()
            ->with('inventory')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name.' — '.__('stok').': '
                    .rtrim(rtrim(number_format((float) ($c->inventory?->stock ?? 0), 3, ',', '.'), '0'), ',')
                    .' '.$c->unit,
                'stock' => (float) ($c->inventory?->stock ?? 0),
                'unit' => $c->unit,
            ])
            ->toArray();
    }

    #[Computed]
    public function productOptions(): array
    {
        return Product::active()
            ->orderBy('name')
            ->get(['id', 'name', 'unit'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name.' ('.$p->unit.')', 'unit' => $p->unit])
            ->toArray();
    }

    #[Computed]
    public function totalInput(): float
    {
        return (float) collect($this->inputs)->sum(fn ($i) => (float) ($i['quantity'] ?? 0));
    }

    public function addInput(): void
    {
        $this->inputs[] = ['waste_category_id' => null, 'quantity' => ''];
    }

    public function removeInput(int $index): void
    {
        unset($this->inputs[$index]);
        $this->inputs = array_values($this->inputs);

        if (empty($this->inputs)) {
            $this->addInput();
        }
    }

    public function addOutput(): void
    {
        $this->outputs[] = ['product_id' => null, 'quantity' => ''];
    }

    public function removeOutput(int $index): void
    {
        unset($this->outputs[$index]);
        $this->outputs = array_values($this->outputs);
    }

    public function rules(): array
    {
        $rules = [
            'notes' => ['nullable', 'string', 'max:1000'],
            'inputs' => ['required', 'array', 'min:1'],
            'inputs.*.waste_category_id' => ['required', 'integer', 'exists:waste_categories,id'],
            'inputs.*.quantity' => ['required', 'numeric', 'gt:0'],
            'outputs' => ['array'],
        ];

        foreach ($this->outputs as $idx => $_) {
            $rules["outputs.{$idx}.product_id"] = ['required', 'integer', 'exists:products,id'];
            $rules["outputs.{$idx}.quantity"] = ['required', 'numeric', 'gt:0'];
        }

        return $rules;
    }

    public function save(ProcessingTransactionService $service): void
    {
        $this->validate();

        try {
            $transaction = $service->create(
                inputs: array_map(fn ($item) => [
                    'waste_category_id' => (int) $item['waste_category_id'],
                    'quantity' => (float) $item['quantity'],
                ], $this->inputs),
                outputs: array_map(fn ($item) => [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                ], $this->outputs),
                notes: $this->notes !== '' ? $this->notes : null,
                createdBy: Auth::user(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->success(__('Pengolahan #:id tersimpan.', ['id' => $transaction->id]));
        $this->redirect(route('admin.processing.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Pengolahan Baru') }}"
        subtitle="{{ __('Input sampah yang dipakai (dari inventory) dan hasil produk olahan yang dihasilkan.') }}"
        separator
    >
        <x-slot:actions>
            <x-mary-button label="{{ __('Batal') }}" icon="o-arrow-uturn-left" link="{{ route('admin.processing.index') }}" />
        </x-slot:actions>
    </x-mary-header>

    <form wire:submit="save" class="max-w-4xl space-y-6">
        {{-- Input section --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold">{{ __('Input: Sampah yang dipakai') }}</h3>
                <x-mary-button
                    icon="o-plus"
                    label="{{ __('Tambah') }}"
                    wire:click="addInput"
                    type="button"
                    class="btn-sm btn-ghost"
                />
            </div>

            @foreach ($inputs as $index => $item)
                @php
                    $categoryOpt = collect($this->categoryOptions)->firstWhere('id', $item['waste_category_id']);
                    $qty = (float) ($item['quantity'] ?? 0);
                @endphp
                <div wire:key="pin-{{ $index }}" class="card bg-base-100 border border-base-300">
                    <div class="card-body p-4 gap-2">
                        <div class="grid gap-3 md:grid-cols-12">
                            <div class="md:col-span-8">
                                <x-mary-select
                                    wire:model.live="inputs.{{ $index }}.waste_category_id"
                                    label="{{ __('Kategori sampah') }}"
                                    :options="$this->categoryOptions"
                                    option-label="name"
                                    option-value="id"
                                    placeholder="{{ __('Pilih kategori') }}"
                                    icon="o-tag"
                                />
                            </div>
                            <div class="md:col-span-3">
                                <x-mary-input
                                    wire:model.live="inputs.{{ $index }}.quantity"
                                    label="{{ __('Berat') }}"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    :suffix="$categoryOpt['unit'] ?? 'kg'"
                                />
                            </div>
                            <div class="md:col-span-1 flex md:items-end">
                                <x-mary-button
                                    icon="o-trash"
                                    wire:click="removeInput({{ $index }})"
                                    type="button"
                                    class="btn-ghost btn-sm w-full text-error"
                                />
                            </div>
                        </div>

                        @if ($categoryOpt && $qty > 0 && $qty > $categoryOpt['stock'])
                            <x-mary-badge value="{{ __('Stok tidak cukup') }}" class="badge-error badge-soft self-start" />
                        @endif

                        @error("inputs.{$index}.waste_category_id")
                            <div class="text-sm text-error">{{ $message }}</div>
                        @enderror
                        @error("inputs.{$index}.quantity")
                            <div class="text-sm text-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Output section --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold">{{ __('Output: Produk yang dihasilkan') }}</h3>
                    <p class="text-xs text-base-content/60">{{ __('Opsional — kosongkan jika hanya memproses tanpa produk jadi.') }}</p>
                </div>
                <x-mary-button
                    icon="o-plus"
                    label="{{ __('Tambah') }}"
                    wire:click="addOutput"
                    type="button"
                    class="btn-sm btn-ghost"
                />
            </div>

            @foreach ($outputs as $index => $item)
                @php
                    $productOpt = collect($this->productOptions)->firstWhere('id', $item['product_id']);
                @endphp
                <div wire:key="pout-{{ $index }}" class="card bg-base-100 border border-base-300">
                    <div class="card-body p-4 gap-2">
                        <div class="grid gap-3 md:grid-cols-12">
                            <div class="md:col-span-8">
                                <x-mary-select
                                    wire:model.live="outputs.{{ $index }}.product_id"
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
                                    wire:model.live="outputs.{{ $index }}.quantity"
                                    label="{{ __('Jumlah') }}"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    :suffix="$productOpt['unit'] ?? 'pcs'"
                                />
                            </div>
                            <div class="md:col-span-1 flex md:items-end">
                                <x-mary-button
                                    icon="o-trash"
                                    wire:click="removeOutput({{ $index }})"
                                    type="button"
                                    class="btn-ghost btn-sm w-full text-error"
                                />
                            </div>
                        </div>

                        @error("outputs.{$index}.product_id")
                            <div class="text-sm text-error">{{ $message }}</div>
                        @enderror
                        @error("outputs.{$index}.quantity")
                            <div class="text-sm text-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <x-mary-textarea wire:model="notes" label="{{ __('Catatan (opsional)') }}" rows="2" />

        <div class="rounded-xl border border-base-300 bg-base-100 p-4 flex items-center justify-between">
            <div class="text-sm text-base-content/70">{{ __('Total input') }}</div>
            <div class="text-lg font-bold">{{ number_format($this->totalInput, 3, ',', '.') }} kg</div>
        </div>

        <div class="flex justify-end gap-2">
            <x-mary-button label="{{ __('Batal') }}" link="{{ route('admin.processing.index') }}" />
            <x-mary-button
                type="submit"
                label="{{ __('Simpan Pengolahan') }}"
                class="btn-primary"
                spinner="save"
                data-test="processing-save-button"
            />
        </div>
    </form>
</section>
