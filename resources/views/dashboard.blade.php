@php
    $user = auth()->user();
    $balance = $user->balance()->first();
    $saldoTertahan = (float) ($balance->saldo_tertahan ?? 0);
    $saldoTersedia = (float) ($balance->saldo_tersedia ?? 0);
    $points = (int) ($balance->points ?? 0);

    $recentSavings = $user->savingTransactions()
        ->with('items')
        ->orderByDesc('transacted_at')
        ->limit(5)
        ->get();

    $rupiah = fn (float $v): string => 'Rp '.number_format($v, 0, ',', '.');
@endphp

<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <x-mary-header
            :title="__('Halo, :name 👋', ['name' => $user->name])"
            subtitle="{{ __('Ringkasan tabungan dan aktivitas Anda di Bank Sampah.') }}"
            separator
        />

        {{-- Balance cards --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body gap-1 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-base-content/60">{{ __('Saldo Tersedia') }}</span>
                        <x-mary-icon name="o-banknotes" class="size-5 text-success" />
                    </div>
                    <div class="text-2xl font-bold text-success">{{ $rupiah($saldoTersedia) }}</div>
                    <div class="text-xs text-base-content/50">{{ __('Siap dicairkan') }}</div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body gap-1 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-base-content/60">{{ __('Saldo Tertahan') }}</span>
                        <x-mary-icon name="o-clock" class="size-5 text-warning" />
                    </div>
                    <div class="text-2xl font-bold text-warning">{{ $rupiah($saldoTertahan) }}</div>
                    <div class="text-xs text-base-content/50">{{ __('Menunggu release admin') }}</div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body gap-1 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-base-content/60">{{ __('Poin') }}</span>
                        <x-mary-icon name="o-sparkles" class="size-5 text-primary" />
                    </div>
                    <div class="text-2xl font-bold text-primary">{{ number_format($points, 0, ',', '.') }}</div>
                    <div class="text-xs text-base-content/50">
                        @if ($user->is_member)
                            {{ __('Tukar dengan merchandise') }}
                        @else
                            {{ __('Daftar member untuk dapat poin') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="grid grid-cols-3 gap-3">
            <x-mary-button link="{{ route('nasabah.transaksi') }}" label="{{ __('Transaksi') }}" icon="o-list-bullet" class="btn-ghost" />
            <x-mary-button link="{{ route('nasabah.pencairan') }}" label="{{ __('Pencairan') }}" icon="o-wallet" class="btn-ghost" />
            <x-mary-button link="{{ route('nasabah.poin') }}" label="{{ __('Histori Poin') }}" icon="o-sparkles" class="btn-ghost" />
        </div>

        {{-- Recent savings --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-base-content/60">
                    {{ __('Transaksi Nabung Terakhir') }}
                </h3>
                <a href="{{ route('nasabah.transaksi') }}" wire:navigate class="link link-primary text-sm">
                    {{ __('Lihat semua') }}
                </a>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Tanggal') }}</th>
                                <th>{{ __('Item') }}</th>
                                <th class="text-right">{{ __('Berat') }}</th>
                                <th class="text-right">{{ __('Nilai') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSavings as $tx)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $tx->transacted_at->format('d M Y') }}</td>
                                    <td>
                                        <div class="text-sm">
                                            {{ $tx->items->count() }} {{ __('kategori') }}
                                        </div>
                                        <div class="text-xs text-base-content/60">
                                            {{ $tx->items->pluck('category_name_snapshot')->join(', ') }}
                                        </div>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        {{ rtrim(rtrim(number_format((float) $tx->total_weight, 3, ',', '.'), '0'), ',') }} kg
                                    </td>
                                    <td class="text-right font-semibold whitespace-nowrap">
                                        {{ $rupiah((float) $tx->total_value) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-base-content/60 py-6">
                                        {{ __('Belum ada transaksi. Kunjungi Bank Sampah untuk mulai nabung!') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
