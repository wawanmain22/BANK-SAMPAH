<?php

use App\Models\ProcessingTransaction;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pengolahan Sampah')] class extends Component {
    use WithPagination;

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'class' => 'w-16'],
            ['key' => 'transacted_at_label', 'label' => __('Tanggal'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'total_input_weight_label', 'label' => __('Total Input'), 'sortable' => false],
            ['key' => 'outputs_label', 'label' => __('Produk Dihasilkan'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
            ['key' => 'notes', 'label' => __('Catatan'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function transactions()
    {
        return ProcessingTransaction::query()
            ->with('outputs')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Pengolahan Sampah') }}"
        subtitle="{{ __('Catat proses sampah diolah menjadi produk hasil olahan. Inventory sampah berkurang, stok produk bertambah.') }}"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                label="{{ __('Pengolahan Baru') }}"
                link="{{ route('admin.processing.create') }}"
                data-test="processing-create-button"
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

        @scope('cell_total_input_weight_label', $row)
            <span class="font-semibold">
                {{ rtrim(rtrim(number_format((float) $row->total_input_weight, 3, ',', '.'), '0'), ',') }}
            </span>
            <span class="text-xs text-base-content/60">kg</span>
        @endscope

        @scope('cell_outputs_label', $row)
            @if ($row->outputs->isEmpty())
                <span class="text-base-content/50 text-sm">{{ __('(tanpa produk)') }}</span>
            @else
                @foreach ($row->outputs as $output)
                    <div class="text-sm">
                        {{ $output->product_name_snapshot }}:
                        <span class="font-medium">
                            {{ rtrim(rtrim(number_format((float) $output->quantity, 3, ',', '.'), '0'), ',') }}
                        </span>
                        <span class="text-xs text-base-content/60">{{ $output->unit_snapshot }}</span>
                    </div>
                @endforeach
            @endif
        @endscope

        @scope('cell_notes', $row)
            <span class="text-sm text-base-content/70">{{ $row->notes ?? '—' }}</span>
        @endscope
    </x-mary-table>
</section>
