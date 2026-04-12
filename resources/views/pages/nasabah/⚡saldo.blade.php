<?php

use App\Models\BalanceHistory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Saldo')] class extends Component {
    use WithPagination;

    public string $bucket = '';

    public function updatingBucket(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'created_at_label', 'label' => __('Tanggal'), 'sortable' => false],
            ['key' => 'bucket_label', 'label' => __('Bucket'), 'sortable' => false],
            ['key' => 'type_label', 'label' => __('Jenis'), 'sortable' => false],
            ['key' => 'amount_label', 'label' => __('Jumlah'), 'sortable' => false],
            ['key' => 'balance_after_label', 'label' => __('Saldo Setelah'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'description', 'label' => __('Keterangan'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function histories()
    {
        return BalanceHistory::query()
            ->where('user_id', Auth::id())
            ->when($this->bucket !== '', fn ($q) => $q->where('bucket', $this->bucket))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function balance()
    {
        return Auth::user()->balance;
    }

    public function bucketOptions(): array
    {
        return [
            ['id' => 'tertahan', 'name' => __('Tertahan')],
            ['id' => 'tersedia', 'name' => __('Tersedia')],
        ];
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Saldo') }}"
        subtitle="{{ __('Rincian saldo dan pergerakannya.') }}"
        separator
    >
        <x-slot:middle class="!justify-end">
            <x-mary-select
                wire:model.live="bucket"
                :options="$this->bucketOptions()"
                option-label="name"
                option-value="id"
                placeholder="{{ __('Semua bucket') }}"
                class="md:w-48"
            />
        </x-slot:middle>
    </x-mary-header>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-6">
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-base-content/60">{{ __('Saldo Tersedia') }}</span>
                    <x-mary-icon name="o-banknotes" class="size-5 text-success" />
                </div>
                <div class="text-2xl font-bold text-success">
                    Rp {{ number_format((float) ($this->balance->saldo_tersedia ?? 0), 2, ',', '.') }}
                </div>
                <div class="text-xs text-base-content/50">{{ __('Siap dicairkan') }}</div>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-base-content/60">{{ __('Saldo Tertahan') }}</span>
                    <x-mary-icon name="o-clock" class="size-5 text-warning" />
                </div>
                <div class="text-2xl font-bold text-warning">
                    Rp {{ number_format((float) ($this->balance->saldo_tertahan ?? 0), 2, ',', '.') }}
                </div>
                <div class="text-xs text-base-content/50">{{ __('Menunggu release admin') }}</div>
            </div>
        </div>
    </div>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->histories"
        with-pagination
        striped
    >
        @scope('cell_created_at_label', $row)
            {{ $row->created_at->format('d M Y H:i') }}
        @endscope

        @scope('cell_bucket_label', $row)
            @if ($row->bucket === 'tertahan')
                <x-mary-badge value="{{ __('Tertahan') }}" class="badge-warning badge-soft" />
            @else
                <x-mary-badge value="{{ __('Tersedia') }}" class="badge-success badge-soft" />
            @endif
        @endscope

        @scope('cell_type_label', $row)
            <span class="text-sm capitalize">{{ $row->type }}</span>
        @endscope

        @scope('cell_amount_label', $row)
            <span @class([
                'font-semibold',
                'text-success' => (float) $row->amount > 0,
                'text-error' => (float) $row->amount < 0,
            ])>
                {{ (float) $row->amount > 0 ? '+' : '' }}
                Rp {{ number_format((float) $row->amount, 0, ',', '.') }}
            </span>
        @endscope

        @scope('cell_balance_after_label', $row)
            Rp {{ number_format((float) $row->balance_after, 0, ',', '.') }}
        @endscope

        @scope('cell_description', $row)
            <span class="text-sm text-base-content/70">{{ $row->description ?? '—' }}</span>
        @endscope
    </x-mary-table>
</section>
