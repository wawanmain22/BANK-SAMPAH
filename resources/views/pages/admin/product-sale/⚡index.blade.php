<?php

use App\Models\ProductSale;
use App\Services\ProductSalesService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Penjualan Produk')] class extends Component {
    use Toast, WithPagination;

    public string $search = '';

    public string $status_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'transacted_at_label', 'label' => __('Tanggal'), 'class' => 'hidden md:table-cell w-36 whitespace-nowrap', 'sortable' => false],
            ['key' => 'buyer_label', 'label' => __('Pembeli'), 'class' => 'w-52', 'sortable' => false],
            ['key' => 'items_label', 'label' => __('Produk'), 'class' => 'w-64 max-w-xs', 'sortable' => false],
            ['key' => 'total_value_label', 'label' => __('Total'), 'class' => 'w-32 whitespace-nowrap', 'sortable' => false],
            ['key' => 'payment_label', 'label' => __('Pembayaran'), 'class' => 'w-36 whitespace-nowrap', 'sortable' => false],
            ['key' => 'action_label', 'label' => __('Aksi'), 'class' => 'w-16 text-center', 'sortable' => false],
        ];
    }

    #[Computed]
    public function sales()
    {
        return ProductSale::query()
            ->with(['items', 'buyer:id,name'])
            ->when($this->search !== '', fn ($q) => $q->where(function ($inner) {
                $inner->where('buyer_name', 'like', '%'.$this->search.'%')
                    ->orWhere('buyer_phone', 'like', '%'.$this->search.'%');
            }))
            ->when($this->status_filter !== '', fn ($q) => $q->where('payment_status', $this->status_filter))
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->paginate(15);
    }

    #[Computed]
    public function summary(): array
    {
        $base = ProductSale::query();
        $paid = (clone $base)->paid()->sum('total_value');
        $pending = (clone $base)->pending()->sum('total_value');
        $count = (clone $base)->count();

        return [
            'paid' => (float) $paid,
            'pending' => (float) $pending,
            'count' => (int) $count,
        ];
    }

    public function statusOptions(): array
    {
        return [
            ['id' => 'paid', 'name' => __('Lunas')],
            ['id' => 'pending', 'name' => __('Belum lunas')],
        ];
    }

    public function markPaid(int $id, ProductSalesService $service): void
    {
        $sale = ProductSale::findOrFail($id);
        $service->markPaid($sale);
        $this->success(__('Transaksi ditandai lunas.'));
        unset($this->sales, $this->summary);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Penjualan Produk') }}"
        subtitle="{{ __('Catatan penjualan produk olahan ke pembeli. Rekap pendapatan per periode ada di dashboard.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <div class="flex flex-col gap-2 md:flex-row">
                <x-mary-input
                    wire:model.live.debounce.300ms="search"
                    icon="o-magnifying-glass"
                    placeholder="{{ __('Cari pembeli / no HP...') }}"
                    clearable
                    class="md:w-56"
                />
                <x-mary-select
                    wire:model.live="status_filter"
                    :options="$this->statusOptions()"
                    option-label="name"
                    option-value="id"
                    placeholder="{{ __('Semua status') }}"
                    class="md:w-40"
                />
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                link="{{ route('admin.product-sale.create') }}"
                label="{{ __('Penjualan Baru') }}"
                data-test="product-sale-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    <section aria-label="{{ __('Ringkasan penjualan produk') }}" class="grid gap-3 md:grid-cols-3 mb-4">
        <article class="rounded-xl border border-base-300 bg-base-100 p-4">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-base-content/70">{{ __('Total Transaksi') }}</h2>
            <p class="mt-1 text-2xl font-bold" aria-label="{{ __(':n transaksi', ['n' => $this->summary['count']]) }}">
                {{ $this->summary['count'] }}
            </p>
        </article>
        <article class="rounded-xl border border-success/30 bg-success/10 p-4">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-success-content/90 dark:text-success">
                <span aria-hidden="true">&#x2713;</span> {{ __('Lunas') }}
            </h2>
            <p class="mt-1 text-2xl font-bold text-success-content dark:text-success" aria-label="{{ __('Total lunas :amt rupiah', ['amt' => number_format($this->summary['paid'], 0, ',', '.')]) }}">
                Rp {{ number_format($this->summary['paid'], 2, ',', '.') }}
            </p>
        </article>
        <article class="rounded-xl border border-warning/30 bg-warning/10 p-4">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-warning-content/90 dark:text-warning">
                <span aria-hidden="true">&#x21bb;</span> {{ __('Belum Lunas') }}
            </h2>
            <p class="mt-1 text-2xl font-bold text-warning-content dark:text-warning" aria-label="{{ __('Total belum lunas :amt rupiah', ['amt' => number_format($this->summary['pending'], 0, ',', '.')]) }}">
                Rp {{ number_format($this->summary['pending'], 2, ',', '.') }}
            </p>
        </article>
    </section>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->sales"
        with-pagination
        striped
    >
        @scope('cell_transacted_at_label', $row)
            {{ $row->transacted_at->format('d M Y H:i') }}
        @endscope

        @scope('cell_buyer_label', $row)
            <div>
                <div class="font-medium">{{ $row->buyer_name }}</div>
                <a
                    href="tel:{{ $row->buyer_phone }}"
                    class="text-xs text-base-content/70 hover:text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary rounded"
                    aria-label="{{ __('Hubungi :phone', ['phone' => $row->buyer_phone]) }}"
                >
                    {{ $row->buyer_phone }}
                </a>
                @if ($row->buyer)
                    <x-mary-badge value="{{ __('Nasabah') }}" class="badge-primary badge-soft badge-xs mt-1" />
                @endif
            </div>
        @endscope

        @scope('cell_items_label', $row)
            <ul class="text-sm space-y-0.5">
                @foreach ($row->items->take(2) as $it)
                    <li>
                        <span class="font-medium">{{ rtrim(rtrim(number_format((float) $it->quantity, 3, ',', '.'), '0'), ',') }}</span>
                        <span aria-hidden="true">×</span>
                        <span class="sr-only">{{ __('dikalikan') }}</span>
                        {{ $it->product_name_snapshot }}
                    </li>
                @endforeach
                @if ($row->items->count() > 2)
                    <li class="text-xs text-base-content/70">
                        {{ __('+:n item lagi', ['n' => $row->items->count() - 2]) }}
                    </li>
                @endif
            </ul>
        @endscope

        @scope('cell_total_value_label', $row)
            <span class="font-semibold" aria-label="{{ __('Total :amt rupiah', ['amt' => number_format((float) $row->total_value, 0, ',', '.')]) }}">
                Rp {{ number_format((float) $row->total_value, 2, ',', '.') }}
            </span>
        @endscope

        @scope('cell_payment_label', $row)
            <div class="flex flex-col gap-1">
                @if ($row->payment_status === 'paid')
                    <x-mary-badge
                        value="{{ __('Lunas') }}"
                        icon="o-check-circle"
                        class="badge-success badge-soft"
                    />
                @else
                    <x-mary-badge
                        value="{{ __('Belum Lunas') }}"
                        icon="o-clock"
                        class="badge-warning badge-soft"
                    />
                @endif
                <span class="text-xs text-base-content/70 uppercase tracking-wide" aria-label="{{ __('Metode :method', ['method' => $row->payment_method]) }}">
                    {{ strtoupper($row->payment_method) }}
                </span>
            </div>
        @endscope

        @scope('cell_action_label', $row)
            <div class="flex justify-center">
                @if ($row->payment_status === 'pending')
                    <x-mary-button
                        icon="o-check-circle"
                        wire:click="markPaid({{ $row->id }})"
                        class="btn-ghost btn-sm text-success cursor-pointer"
                        tooltip="{{ __('Tandai Lunas') }}"
                        aria-label="{{ __('Tandai transaksi #:id (:buyer) sebagai lunas', ['id' => $row->id, 'buyer' => $row->buyer_name]) }}"
                        wire:confirm="{{ __('Tandai transaksi ini sebagai lunas?') }}"
                        data-test="product-sale-mark-paid-{{ $row->id }}"
                    />
                @else
                    <span class="text-base-content/30" aria-hidden="true">—</span>
                    <span class="sr-only">{{ __('Tidak ada aksi') }}</span>
                @endif
            </div>
        @endscope
    </x-mary-table>
</section>
