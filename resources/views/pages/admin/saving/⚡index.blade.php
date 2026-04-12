<?php

use App\Models\SavingTransaction;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Transaksi Nabung')] class extends Component {
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
            ['key' => 'user_name', 'label' => __('Nasabah'), 'sortable' => false],
            ['key' => 'total_weight_label', 'label' => __('Berat (kg)'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
            ['key' => 'total_value_label', 'label' => __('Nilai'), 'sortable' => false],
            ['key' => 'points_awarded', 'label' => __('Poin'), 'class' => 'hidden lg:table-cell'],
        ];
    }

    #[Computed]
    public function transactions()
    {
        return SavingTransaction::query()
            ->with('user:id,name,email')
            ->when($this->search !== '', function ($q) {
                $q->whereHas('user', function ($q) {
                    $like = '%'.$this->search.'%';
                    $q->where('name', 'like', $like)->orWhere('email', 'like', $like);
                });
            })
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Transaksi Nabung') }}"
        subtitle="{{ __('Riwayat semua transaksi nabung nasabah.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                placeholder="{{ __('Cari nasabah...') }}"
                clearable
            />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                label="{{ __('Nabung Baru') }}"
                link="{{ route('admin.saving.create') }}"
                data-test="saving-create-button"
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

        @scope('cell_user_name', $row)
            <div>
                <div class="font-medium">{{ $row->user->name }}</div>
                <div class="text-xs text-base-content/60">{{ $row->user->email }}</div>
            </div>
        @endscope

        @scope('cell_total_weight_label', $row)
            {{ rtrim(rtrim(number_format((float) $row->total_weight, 3, ',', '.'), '0'), ',') }}
        @endscope

        @scope('cell_total_value_label', $row)
            <span class="font-medium">Rp {{ number_format((float) $row->total_value, 0, ',', '.') }}</span>
        @endscope

        @scope('cell_points_awarded', $row)
            @if ($row->points_awarded > 0)
                <x-mary-badge :value="$row->points_awarded.' poin'" class="badge-success badge-soft" />
            @else
                <span class="text-base-content/40">—</span>
            @endif
        @endscope
    </x-mary-table>
</section>
