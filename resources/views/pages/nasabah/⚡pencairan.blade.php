<?php

use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pencairan')] class extends Component {
    use WithPagination;

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'class' => 'w-16'],
            ['key' => 'processed_at_label', 'label' => __('Tanggal'), 'sortable' => false],
            ['key' => 'amount_label', 'label' => __('Jumlah'), 'sortable' => false],
            ['key' => 'method_label', 'label' => __('Metode'), 'sortable' => false],
            ['key' => 'notes', 'label' => __('Catatan'), 'class' => 'hidden md:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function withdrawals()
    {
        return WithdrawalRequest::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Pencairan') }}"
        subtitle="{{ __('Riwayat pencairan saldo Anda via cash atau transfer.') }}"
        separator
    />

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->withdrawals"
        with-pagination
        striped
    >
        @scope('cell_processed_at_label', $row)
            {{ $row->processed_at->format('d M Y H:i') }}
        @endscope

        @scope('cell_amount_label', $row)
            <span class="font-semibold">Rp {{ number_format((float) $row->amount, 0, ',', '.') }}</span>
        @endscope

        @scope('cell_method_label', $row)
            @if ($row->method === 'transfer')
                <x-mary-badge value="{{ __('Transfer') }}" class="badge-info badge-soft" />
                @if ($row->bank_name)
                    <div class="text-xs text-base-content/60 mt-1">
                        {{ $row->bank_name }} • {{ $row->account_number }}
                    </div>
                @endif
            @else
                <x-mary-badge value="{{ __('Cash') }}" class="badge-success badge-soft" />
            @endif
        @endscope

        @scope('cell_notes', $row)
            <span class="text-sm text-base-content/70">{{ $row->notes ?? '—' }}</span>
        @endscope
    </x-mary-table>
</section>
