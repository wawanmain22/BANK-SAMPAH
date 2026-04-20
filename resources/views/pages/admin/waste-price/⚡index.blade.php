<?php

use App\Concerns\WastePriceValidationRules;
use App\Models\WasteCategory;
use App\Models\WasteItem;
use App\Models\WastePrice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Harga Sampah')] class extends Component {
    use Toast, WastePriceValidationRules, WithPagination;

    public string $search = '';

    public ?int $category_filter = null;

    public ?int $waste_item_id = null;

    public string $price_per_unit = '';

    public string $effective_from = '';

    public string $notes = '';

    public ?int $historyItemId = null;

    public bool $formModal = false;

    public bool $historyModal = false;

    public function mount(): void
    {
        $this->effective_from = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'code', 'label' => __('Kode'), 'class' => 'w-20'],
            ['key' => 'name', 'label' => __('Barang')],
            ['key' => 'category_label', 'label' => __('Kategori'), 'class' => 'hidden md:table-cell'],
            ['key' => 'current_price_label', 'label' => __('Harga Aktif'), 'sortable' => false],
            ['key' => 'effective_from_label', 'label' => __('Berlaku Sejak'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function items()
    {
        return WasteItem::query()
            ->active()
            ->with(['category:id,name,code_prefix', 'currentPrice'])
            ->when($this->search !== '', fn ($q) => $q->where(function ($inner) {
                $inner->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            }))
            ->when($this->category_filter, fn ($q) => $q->where('waste_category_id', $this->category_filter))
            ->orderBy('code')
            ->paginate(20);
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return WasteCategory::query()
            ->active()
            ->orderBy('code_prefix')
            ->get(['id', 'name', 'code_prefix'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => "{$c->code_prefix} — {$c->name}"])
            ->toArray();
    }

    #[Computed]
    public function historyItem(): ?WasteItem
    {
        if (! $this->historyItemId) {
            return null;
        }

        return WasteItem::with(['prices' => fn ($q) => $q->orderByDesc('effective_from')->orderByDesc('id')])
            ->find($this->historyItemId);
    }

    public function rules(): array
    {
        return $this->wastePriceRules();
    }

    public function startSettingPrice(int $itemId): void
    {
        $this->resetForm();
        $this->waste_item_id = $itemId;
        $this->formModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            WastePrice::create([
                ...$validated,
                'created_by' => Auth::id(),
            ]);

            // Sync the denormalized current price on the item for quick lookups.
            WasteItem::whereKey($validated['waste_item_id'])
                ->update(['price_per_unit' => (float) $validated['price_per_unit']]);
        });

        $this->formModal = false;
        $this->success(__('Harga baru tersimpan.'));
        $this->resetForm();
        unset($this->items);
    }

    public function showHistory(int $itemId): void
    {
        $this->historyItemId = $itemId;
        $this->historyModal = true;
    }

    private function resetForm(): void
    {
        $this->reset(['waste_item_id', 'price_per_unit', 'notes']);
        $this->effective_from = now()->toDateString();
        $this->resetErrorBag();
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Harga Sampah') }}"
        subtitle="{{ __('Set harga baru per barang. Harga lama tetap tersimpan sebagai riwayat dan transaksi lama tidak ikut berubah.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <div class="flex flex-col gap-2 md:flex-row">
                <x-mary-input
                    wire:model.live.debounce.300ms="search"
                    icon="o-magnifying-glass"
                    placeholder="{{ __('Cari barang / kode...') }}"
                    clearable
                    class="md:w-56"
                />
                <x-mary-select
                    wire:model.live="category_filter"
                    :options="$this->categoryOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="{{ __('Semua kategori') }}"
                    icon="o-tag"
                    class="md:w-56"
                />
            </div>
        </x-slot:middle>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->items"
        with-pagination
        striped
    >
        @scope('cell_code', $row)
            <span class="font-mono text-sm">{{ $row->code }}</span>
        @endscope

        @scope('cell_name', $row)
            <div class="font-medium">{{ $row->name }}</div>
        @endscope

        @scope('cell_category_label', $row)
            <x-mary-badge value="{{ $row->category?->name }}" class="badge-neutral badge-soft" />
        @endscope

        @scope('cell_current_price_label', $row)
            @if ($row->currentPrice)
                <span class="font-medium">
                    Rp {{ number_format((float) $row->currentPrice->price_per_unit, 2, ',', '.') }}
                </span>
                <span class="text-xs text-base-content/60">/{{ $row->unit }}</span>
            @else
                <x-mary-badge value="{{ __('Belum ada') }}" class="badge-warning badge-soft" />
            @endif
        @endscope

        @scope('cell_effective_from_label', $row)
            {{ $row->currentPrice?->effective_from?->format('d M Y') ?? '—' }}
        @endscope

        @scope('actions', $row)
            <div class="flex items-center gap-1">
                <x-mary-button
                    icon="o-banknotes"
                    wire:click="startSettingPrice({{ $row->id }})"
                    class="btn-primary btn-sm"
                    label="{{ __('Set Harga') }}"
                    responsive
                    data-test="price-set-{{ $row->id }}"
                />
                <x-mary-button
                    icon="o-clock"
                    wire:click="showHistory({{ $row->id }})"
                    class="btn-ghost btn-sm"
                    label="{{ __('Riwayat') }}"
                    responsive
                    data-test="price-history-{{ $row->id }}"
                />
            </div>
        @endscope
    </x-mary-table>

    @if ($this->items->isEmpty())
        <div class="rounded-xl border border-base-300 bg-base-100 p-8 text-center text-base-content/60">
            {{ __('Belum ada barang aktif. Tambahkan dulu di menu Barang Sampah.') }}
        </div>
    @endif

    <x-mary-modal
        wire:model="formModal"
        title="{{ __('Set Harga Baru') }}"
        subtitle="{{ __('Harga akan berlaku mulai tanggal efektif. Harga lama tersimpan di riwayat.') }}"
        separator
        box-class="max-w-lg"
    >
        <x-mary-form wire:submit="save" no-separator>
            <x-mary-input
                wire:model="price_per_unit"
                label="{{ __('Harga per satuan (Rp)') }}"
                icon="o-banknotes"
                type="number"
                step="0.01"
                min="0"
                required
            />
            <x-mary-input
                wire:model="effective_from"
                label="{{ __('Berlaku sejak') }}"
                icon="o-calendar"
                type="date"
                required
            />
            <x-mary-textarea wire:model="notes" label="{{ __('Catatan (opsional)') }}" rows="2" />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button
                    label="{{ __('Simpan') }}"
                    class="btn-primary"
                    type="submit"
                    spinner="save"
                    data-test="price-save-button"
                />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal
        wire:model="historyModal"
        title="{{ __('Riwayat Harga') }}"
        subtitle="{{ $this->historyItem?->code }} — {{ $this->historyItem?->name }}"
        separator
        box-class="max-w-xl"
    >
        @if ($this->historyItem)
            <div class="max-h-[60vh] overflow-y-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>{{ __('Berlaku') }}</th>
                            <th>{{ __('Harga') }}</th>
                            <th>{{ __('Catatan') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->historyItem->prices as $price)
                            <tr wire:key="price-{{ $price->id }}">
                                <td class="whitespace-nowrap">{{ $price->effective_from->format('d M Y') }}</td>
                                <td class="whitespace-nowrap">Rp {{ number_format((float) $price->price_per_unit, 2, ',', '.') }}</td>
                                <td class="text-sm text-base-content/70">{{ $price->notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-base-content/60">
                                    {{ __('Belum ada riwayat harga.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="{{ __('Tutup') }}" @click="$wire.historyModal = false" />
        </x-slot:actions>
    </x-mary-modal>
</section>
