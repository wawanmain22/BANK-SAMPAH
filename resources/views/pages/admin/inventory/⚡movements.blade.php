<?php

use App\Models\InventoryMovement;
use App\Models\WasteCategory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Riwayat Inventory')] class extends Component {
    use WithPagination;

    public ?int $category_id = null;

    public string $direction = '';

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingDirection(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'created_at_label', 'label' => __('Tanggal'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'category_name', 'label' => __('Kategori'), 'sortable' => false],
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
            ->with('category:id,name,unit')
            ->when($this->category_id, fn ($q) => $q->where('waste_category_id', $this->category_id))
            ->when($this->direction !== '', fn ($q) => $q->where('direction', $this->direction))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return WasteCategory::orderBy('name')->get(['id', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();
    }

    public function directionOptions(): array
    {
        return [
            ['id' => 'in', 'name' => __('Masuk')],
            ['id' => 'out', 'name' => __('Keluar')],
        ];
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Riwayat Pergerakan Inventory') }}"
        subtitle="{{ __('Log semua transaksi stok: masuk dari nabung/sedekah, keluar ke mitra/olahan.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <div class="flex flex-col gap-2 md:flex-row">
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
                    wire:model.live="direction"
                    :options="$this->directionOptions()"
                    option-label="name"
                    option-value="id"
                    placeholder="{{ __('Semua arah') }}"
                    class="md:w-40"
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

        @scope('cell_category_name', $row)
            {{ $row->category?->name ?? '—' }}
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
                <span class="text-xs text-base-content/60">{{ $row->category?->unit }}</span>
            </span>
        @endscope

        @scope('cell_stock_after_label', $row)
            {{ rtrim(rtrim(number_format((float) $row->stock_after, 3, ',', '.'), '0'), ',') }}
            <span class="text-xs text-base-content/60">{{ $row->category?->unit }}</span>
        @endscope
    </x-mary-table>
</section>
