<?php

use App\Models\WasteCategory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Inventory Sampah')] class extends Component {
    public string $search = '';

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('Kategori')],
            ['key' => 'unit', 'label' => __('Satuan'), 'class' => 'hidden md:table-cell'],
            ['key' => 'stock_label', 'label' => __('Stok'), 'sortable' => false],
            ['key' => 'status_label', 'label' => __('Status'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function inventories()
    {
        return WasteCategory::query()
            ->with('inventory')
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get();
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Inventory Sampah') }}"
        subtitle="{{ __('Stok tiap kategori sampah hasil akumulasi dari nabung, sedekah, penjualan, dan pengolahan.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                placeholder="{{ __('Cari kategori...') }}"
                clearable
            />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button
                icon="o-clock"
                label="{{ __('Riwayat Pergerakan') }}"
                link="{{ route('admin.inventory.movements') }}"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->inventories"
        striped
    >
        @scope('cell_name', $row)
            <div class="font-medium">{{ $row->name }}</div>
        @endscope

        @scope('cell_stock_label', $row)
            @php
                $stock = (float) ($row->inventory?->stock ?? 0);
            @endphp
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
            @elseif ((float) ($row->inventory?->stock ?? 0) > 0)
                <x-mary-badge value="{{ __('Tersedia') }}" class="badge-success badge-soft" />
            @else
                <x-mary-badge value="{{ __('Kosong') }}" class="badge-warning badge-soft" />
            @endif
        @endscope
    </x-mary-table>
</section>
