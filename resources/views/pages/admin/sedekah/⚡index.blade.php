<?php

use App\Models\SedekahTransaction;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Transaksi Sedekah')] class extends Component {
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
            ['key' => 'donor', 'label' => __('Donor'), 'sortable' => false],
            ['key' => 'total_weight_label', 'label' => __('Berat'), 'sortable' => false],
            ['key' => 'notes', 'label' => __('Catatan'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function transactions()
    {
        return SedekahTransaction::query()
            ->with('user:id,name,email')
            ->when($this->search !== '', function ($q) {
                $like = '%'.$this->search.'%';
                $q->where('donor_name', 'like', $like)
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Transaksi Sedekah') }}"
        subtitle="{{ __('Catatan sampah sumbangan — tidak menghasilkan saldo atau poin, langsung masuk inventory.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                placeholder="{{ __('Cari donor...') }}"
                clearable
            />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                label="{{ __('Sedekah Baru') }}"
                link="{{ route('admin.sedekah.create') }}"
                data-test="sedekah-create-button"
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

        @scope('cell_donor', $row)
            <div class="font-medium">{{ $row->donor_name ?: ($row->user?->name ?? __('Anonim')) }}</div>
            @if ($row->user)
                <div class="text-xs text-base-content/60">{{ $row->user->email }}</div>
            @endif
        @endscope

        @scope('cell_total_weight_label', $row)
            <span class="font-semibold">
                {{ rtrim(rtrim(number_format((float) $row->total_weight, 3, ',', '.'), '0'), ',') }}
            </span>
            <span class="text-xs text-base-content/60">kg</span>
        @endscope

        @scope('cell_notes', $row)
            <span class="text-sm text-base-content/70">{{ $row->notes ?? '—' }}</span>
        @endscope
    </x-mary-table>
</section>
