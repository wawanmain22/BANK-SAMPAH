<?php

use App\Models\Inventory;
use App\Models\WasteCategory;
use App\Models\WasteItem;
use App\Services\InventoryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Inventory Sampah')] class extends Component {
    public string $search = '';

    public ?int $category_filter = null;

    #[Url(as: 'source')]
    public string $source = InventoryService::SOURCE_NABUNG;

    public function mount(): void
    {
        if (! in_array($this->source, [InventoryService::SOURCE_NABUNG, InventoryService::SOURCE_SEDEKAH], true)) {
            $this->source = InventoryService::SOURCE_NABUNG;
        }
    }

    public function updatingSource(): void
    {
        // Allow URL to sync when source changes via tab click.
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'code', 'label' => __('Kode'), 'class' => 'w-20'],
            ['key' => 'name', 'label' => __('Barang')],
            ['key' => 'category_label', 'label' => __('Kategori'), 'class' => 'hidden md:table-cell'],
            ['key' => 'unit', 'label' => __('Satuan'), 'class' => 'hidden md:table-cell'],
            ['key' => 'stock_label', 'label' => __('Stok'), 'sortable' => false],
            ['key' => 'status_label', 'label' => __('Status'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function rows()
    {
        $stocks = Inventory::query()
            ->where('source', $this->source)
            ->pluck('stock', 'waste_item_id');

        return WasteItem::query()
            ->with('category:id,name,code_prefix')
            ->when($this->search !== '', fn ($q) => $q->where(function ($inner) {
                $inner->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            }))
            ->when($this->category_filter, fn ($q) => $q->where('waste_category_id', $this->category_filter))
            ->orderBy('code')
            ->get()
            ->map(function (WasteItem $item) use ($stocks) {
                $item->setAttribute('stock', (float) ($stocks[$item->id] ?? 0));

                return $item;
            });
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return WasteCategory::query()
            ->orderBy('code_prefix')
            ->get(['id', 'name', 'code_prefix'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => "{$c->code_prefix} — {$c->name}"])
            ->toArray();
    }

    public function setSource(string $source): void
    {
        if (in_array($source, [InventoryService::SOURCE_NABUNG, InventoryService::SOURCE_SEDEKAH], true)) {
            $this->source = $source;
        }
    }

    public function sourceLabel(): string
    {
        return $this->source === InventoryService::SOURCE_NABUNG ? __('Sampah Nabung') : __('Sampah Sedekah');
    }

    public function sourceHint(): string
    {
        return $this->source === InventoryService::SOURCE_NABUNG
            ? __('Stok dari nabung nasabah — dijual ke mitra.')
            : __('Stok dari sedekah — dipakai untuk pengolahan jadi produk.');
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Inventory') }}: {{ $this->sourceLabel() }}"
        subtitle="{{ $this->sourceHint() }}"
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
        <x-slot:actions>
            <x-mary-button
                icon="o-clock"
                label="{{ __('Riwayat Pergerakan') }}"
                link="{{ route('admin.inventory.movements', ['source' => $source]) }}"
            />
        </x-slot:actions>
    </x-mary-header>

    <div role="tablist" class="tabs tabs-boxed mb-4 w-fit">
        <button
            type="button"
            wire:click="setSource('nabung')"
            class="tab {{ $source === 'nabung' ? 'tab-active' : '' }}"
            data-test="inventory-tab-nabung"
        >
            {{ __('Sampah Nabung') }}
        </button>
        <button
            type="button"
            wire:click="setSource('sedekah')"
            class="tab {{ $source === 'sedekah' ? 'tab-active' : '' }}"
            data-test="inventory-tab-sedekah"
        >
            {{ __('Sampah Sedekah') }}
        </button>
    </div>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->rows"
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

        @scope('cell_stock_label', $row)
            @php $stock = (float) $row->stock; @endphp
            <span @class([
                'font-semibold',
                'text-success' => $stock > 0,
                'text-base-content/40' => $stock <= 0,
            ])>
                {{ rtrim(rtrim(number_format($stock, 3, ',', '.'), '0'), ',') }}
                <span class="text-xs text-base-content/60">{{ $row->unit }}</span>
            </span>
        @endscope

        @scope('cell_status_label', $row)
            @if (! $row->is_active)
                <x-mary-badge value="{{ __('Non-aktif') }}" class="badge-ghost" />
            @elseif ((float) $row->stock > 0)
                <x-mary-badge value="{{ __('Tersedia') }}" class="badge-success badge-soft" />
            @else
                <x-mary-badge value="{{ __('Kosong') }}" class="badge-warning badge-soft" />
            @endif
        @endscope
    </x-mary-table>
</section>
