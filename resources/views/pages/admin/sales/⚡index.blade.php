<?php

use App\Models\SalesTransaction;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Penjualan')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'class' => 'w-16'],
            ['key' => 'transacted_at_label', 'label' => __('Tanggal'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'partner_name', 'label' => __('Mitra'), 'sortable' => false],
            ['key' => 'total_weight_label', 'label' => __('Berat'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
            ['key' => 'total_value_label', 'label' => __('Total Nilai'), 'sortable' => false],
        ];
    }

    #[Computed]
    public function transactions()
    {
        return SalesTransaction::query()
            ->with('partner:id,name')
            ->when($this->search !== '', function ($q) {
                $q->whereHas('partner', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'));
            })
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Penjualan Sampah') }}"
        subtitle="{{ __('Catatan penjualan sampah ke mitra pengepul/pabrik. Stok inventory otomatis berkurang.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                placeholder="{{ __('Cari mitra...') }}"
                clearable
            />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                label="{{ __('Penjualan Baru') }}"
                link="{{ route('admin.sales.create') }}"
                data-test="sales-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->transactions"
        with-pagination
        striped
    >
        @scope('cell_transacted_at_label', $row)
            {{ $row->transacted_at->format('d M Y H:i') }}
        @endscope

        @scope('cell_partner_name', $row)
            <div class="font-medium">{{ $row->partner?->name ?? '—' }}</div>
        @endscope

        @scope('cell_total_weight_label', $row)
            {{ rtrim(rtrim(number_format((float) $row->total_weight, 3, ',', '.'), '0'), ',') }}
            <span class="text-xs text-base-content/60">kg</span>
        @endscope

        @scope('cell_total_value_label', $row)
            <span class="font-semibold">Rp {{ number_format((float) $row->total_value, 0, ',', '.') }}</span>
        @endscope
    </x-mary-table>
</section>
