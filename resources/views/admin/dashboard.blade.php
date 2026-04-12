@php
    use App\Models\Balance;
    use App\Models\SavingTransaction;
    use App\Models\User;
    use App\Models\WasteCategory;
    use App\Models\WithdrawalRequest;

    $nasabahCount = User::nasabah()->count();
    $memberCount = User::nasabah()->where('is_member', true)->count();
    $categoryCount = WasteCategory::active()->count();

    $totalTertahan = (float) Balance::sum('saldo_tertahan');
    $totalTersedia = (float) Balance::sum('saldo_tersedia');

    $startOfMonth = now()->startOfMonth();

    $savingMonth = SavingTransaction::where('transacted_at', '>=', $startOfMonth)
        ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(total_value), 0) as total_value, COALESCE(SUM(total_weight), 0) as total_weight')
        ->first();

    $withdrawalMonth = WithdrawalRequest::where('processed_at', '>=', $startOfMonth)
        ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount')
        ->first();

    $recentSavings = SavingTransaction::with('user:id,name,email')
        ->orderByDesc('transacted_at')
        ->orderByDesc('id')
        ->limit(5)
        ->get();

    $rupiah = fn (float $value): string => 'Rp '.number_format($value, 0, ',', '.');
@endphp

<x-layouts::app :title="__('Admin Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <x-mary-header
            title="{{ __('Admin Dashboard') }}"
            subtitle="{{ __('Ringkasan operasional Bank Sampah.') }}"
            separator
        />

        {{-- Stats utama --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body gap-1 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-base-content/60">{{ __('Nasabah') }}</span>
                        <x-mary-icon name="o-users" class="size-5 text-primary" />
                    </div>
                    <div class="text-2xl font-bold">{{ number_format($nasabahCount) }}</div>
                    <div class="text-xs text-base-content/50">
                        {{ number_format($memberCount) }} {{ __('member') }}
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body gap-1 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-base-content/60">{{ __('Saldo Tertahan') }}</span>
                        <x-mary-icon name="o-clock" class="size-5 text-warning" />
                    </div>
                    <div class="text-2xl font-bold text-warning">{{ $rupiah($totalTertahan) }}</div>
                    <div class="text-xs text-base-content/50">{{ __('Menunggu mitra') }}</div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body gap-1 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-base-content/60">{{ __('Saldo Tersedia') }}</span>
                        <x-mary-icon name="o-banknotes" class="size-5 text-success" />
                    </div>
                    <div class="text-2xl font-bold text-success">{{ $rupiah($totalTersedia) }}</div>
                    <div class="text-xs text-base-content/50">{{ __('Siap dicairkan') }}</div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body gap-1 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-base-content/60">{{ __('Kategori') }}</span>
                        <x-mary-icon name="o-tag" class="size-5 text-accent" />
                    </div>
                    <div class="text-2xl font-bold">{{ number_format($categoryCount) }}</div>
                    <div class="text-xs text-base-content/50">{{ __('Jenis sampah aktif') }}</div>
                </div>
            </div>
        </div>

        {{-- Stats bulan ini --}}
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-base-content/60 mb-3">
                {{ __('Bulan ini') }}
            </h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="card bg-base-100 border border-base-300">
                    <div class="card-body gap-1 p-5">
                        <div class="text-sm text-base-content/60">{{ __('Transaksi Nabung') }}</div>
                        <div class="text-xl font-bold">{{ number_format((int) $savingMonth->total_count) }}</div>
                        <div class="text-xs text-base-content/50">
                            {{ __('Total nilai') }}: {{ $rupiah((float) $savingMonth->total_value) }}
                        </div>
                        <div class="text-xs text-base-content/50">
                            {{ __('Berat') }}:
                            {{ rtrim(rtrim(number_format((float) $savingMonth->total_weight, 3, ',', '.'), '0'), ',') }} kg
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 border border-base-300">
                    <div class="card-body gap-1 p-5">
                        <div class="text-sm text-base-content/60">{{ __('Pencairan') }}</div>
                        <div class="text-xl font-bold">{{ number_format((int) $withdrawalMonth->total_count) }}</div>
                        <div class="text-xs text-base-content/50">
                            {{ __('Total') }}: {{ $rupiah((float) $withdrawalMonth->total_amount) }}
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 border border-base-300">
                    <div class="card-body gap-1 p-5">
                        <div class="text-sm text-base-content/60">{{ __('Aksi cepat') }}</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <x-mary-button
                                label="{{ __('Nabung Baru') }}"
                                icon="o-plus"
                                class="btn-primary btn-sm"
                                link="{{ route('admin.saving.create') }}"
                            />
                            <x-mary-button
                                label="{{ __('Pencairan') }}"
                                icon="o-wallet"
                                class="btn-sm"
                                link="{{ route('admin.withdrawal.index') }}"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transaksi terbaru --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-base-content/60">
                    {{ __('Transaksi Nabung Terbaru') }}
                </h3>
                <a href="{{ route('admin.saving.index') }}" wire:navigate class="link link-primary text-sm">
                    {{ __('Lihat semua') }}
                </a>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Tanggal') }}</th>
                                <th>{{ __('Nasabah') }}</th>
                                <th class="text-right">{{ __('Nilai') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSavings as $tx)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $tx->transacted_at->format('d M Y H:i') }}</td>
                                    <td>
                                        <div class="font-medium">{{ $tx->user?->name ?? '—' }}</div>
                                        <div class="text-xs text-base-content/60">{{ $tx->user?->email }}</div>
                                    </td>
                                    <td class="text-right font-semibold">{{ $rupiah((float) $tx->total_value) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-base-content/60 py-6">
                                        {{ __('Belum ada transaksi nabung.') }}
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
