<?php

use App\Models\InventoryMovement;
use App\Models\WasteCategory;
use App\Models\WasteItem;
use App\Services\InventoryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Riwayat Inventory')] class extends Component {
    use WithPagination;

    public ?int $item_id = null;

    public ?int $category_id = null;

    public string $direction = '';

    #[Url(as: 'source')]
    public string $source = '';

    public function mount(): void
    {
        if (! in_array($this->source, ['', InventoryService::SOURCE_NABUNG, InventoryService::SOURCE_SEDEKAH], true)) {
            $this->source = '';
        }
    }

    public function updatingItemId(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingDirection(): void
    {
        $this->resetPage();
    }

    public function updatingSource(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'created_at_label', 'label' => __('Tanggal'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'item_label', 'label' => __('Barang'), 'sortable' => false],
            ['key' => 'source_label', 'label' => __('Pool'), 'sortable' => false],
            ['key' => 'direction_label', 'label' => __('Arah'), 'sortable' => false],
            ['key' => 'reason_label', 'label' => __('Sumber'), 'sortable' => false],
            ['key' => 'quantity_label', 'label' => __('Jumlah'), 'sortable' => false],
            ['key' => 'stock_after_label', 'label' => __('Stok Akhir'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function movements()
    {
        return InventoryMovement::query()
            ->with(['item:id,name,code,unit,waste_category_id', 'item.category:id,name'])
            ->when($this->item_id, fn ($q) => $q->where('waste_item_id', $this->item_id))
            ->when($this->category_id, fn ($q) => $q->whereHas('item', fn ($sq) => $sq->where('waste_category_id', $this->category_id)))
            ->when($this->direction !== '', fn ($q) => $q->where('direction', $this->direction))
            ->when($this->source !== '', fn ($q) => $q->where('source', $this->source))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return WasteCategory::orderBy('code_prefix')->get(['id', 'name', 'code_prefix'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => "{$c->code_prefix} — {$c->name}"])
            ->toArray();
    }

    #[Computed]
    public function itemOptions(): array
    {
        return WasteItem::active()
            ->when($this->category_id, fn ($q) => $q->where('waste_category_id', $this->category_id))
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn ($i) => ['id' => $i->id, 'name' => "{$i->code} — {$i->name}"])
            ->toArray();
    }

    public function directionOptions(): array
    {
        return [
            ['id' => 'in', 'name' => __('Masuk')],
            ['id' => 'out', 'name' => __('Keluar')],
        ];
    }

    public function sourceOptions(): array
    {
        return [
            ['id' => InventoryService::SOURCE_NABUNG, 'name' => __('Nabung')],
            ['id' => InventoryService::SOURCE_SEDEKAH, 'name' => __('Sedekah')],
        ];
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Riwayat Pergerakan Inventory') }}"
        subtitle="{{ __('Log semua transaksi stok — per barang, per pool (nabung / sedekah).') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <div class="grid grid-cols-2 gap-2 md:flex md:flex-row">
                <x-mary-select
                    wire:model.live="source"
                    :options="$this->sourceOptions()"
                    option-label="name"
                    option-value="id"
                    placeholder="{{ __('Semua pool') }}"
                    class="md:w-36"
                />
                <x-mary-select
                    wire:model.live="category_id"
                    :options="$this->categoryOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="{{ __('Semua kategori') }}"
                    icon="o-tag"
                    class="md:w-48"
                />
                <x-mary-select
                    wire:model.live="item_id"
                    :options="$this->itemOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="{{ __('Semua barang') }}"
                    class="md:w-48"
                />
                <x-mary-select
                    wire:model.live="direction"
                    :options="$this->directionOptions()"
                    option-label="name"
                    option-value="id"
                    placeholder="{{ __('Semua arah') }}"
                    class="md:w-36"
                />
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button label="{{ __('Kembali') }}" icon="o-arrow-uturn-left" link="{{ route('admin.inventory.index') }}" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->movements"
        with-pagination
        striped
    >
        @scope('cell_created_at_label', $row)
            {{ $row->created_at->format('d M Y H:i') }}
        @endscope

        @scope('cell_item_label', $row)
            <div>
                <div class="font-medium">{{ $row->item?->name ?? '—' }}</div>
                <div class="text-xs text-base-content/60 font-mono">{{ $row->item?->code ?? '—' }}</div>
            </div>
        @endscope

        @scope('cell_source_label', $row)
            @if ($row->source === 'nabung')
                <x-mary-badge value="{{ __('Nabung') }}" class="badge-primary badge-soft" />
            @else
                <x-mary-badge value="{{ __('Sedekah') }}" class="badge-secondary badge-soft" />
            @endif
        @endscope

        @scope('cell_direction_label', $row)
            @if ($row->direction === 'in')
                <x-mary-badge value="{{ __('Masuk') }}" class="badge-success badge-soft" />
            @else
                <x-mary-badge value="{{ __('Keluar') }}" class="badge-error badge-soft" />
            @endif
        @endscope

        @scope('cell_reason_label', $row)
            <span class="text-sm">{{ ucfirst($row->reason) }}</span>
            @if ($row->notes)
                <div class="text-xs text-base-content/60 line-clamp-1">{{ $row->notes }}</div>
            @endif
        @endscope

        @scope('cell_quantity_label', $row)
            <span @class([
                'font-semibold',
                'text-success' => $row->direction === 'in',
                'text-error' => $row->direction === 'out',
            ])>
                {{ $row->direction === 'in' ? '+' : '−' }}
                {{ rtrim(rtrim(number_format((float) $row->quantity, 3, ',', '.'), '0'), ',') }}
                <span class="text-xs text-base-content/60">{{ $row->item?->unit }}</span>
            </span>
        @endscope

        @scope('cell_stock_after_label', $row)
            {{ rtrim(rtrim(number_format((float) $row->stock_after, 3, ',', '.'), '0'), ',') }}
            <span class="text-xs text-base-content/60">{{ $row->item?->unit }}</span>
        @endscope
    </x-mary-table>
</section>
