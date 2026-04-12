<?php

use App\Models\SavingTransaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Transaksi Nabung')] class extends Component {
    use WithPagination;

    #[Computed]
    public function transactions()
    {
        return SavingTransaction::query()
            ->where('user_id', Auth::id())
            ->with('items')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Transaksi Nabung') }}"
        subtitle="{{ __('Riwayat semua transaksi nabung Anda.') }}"
        separator
    />

    <div class="space-y-3">
        @forelse ($this->transactions as $tx)
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <div class="font-semibold">#{{ $tx->id }} • {{ $tx->transacted_at->format('d M Y H:i') }}</div>
                            @if ($tx->notes)
                                <div class="text-xs text-base-content/60">{{ $tx->notes }}</div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-primary">
                                Rp {{ number_format((float) $tx->total_value, 0, ',', '.') }}
                            </div>
                            @if ($tx->points_awarded > 0)
                                <div class="text-xs text-success">+{{ $tx->points_awarded }} poin</div>
                            @endif
                        </div>
                    </div>

                    <table class="table table-sm">
                        <tbody>
                            @foreach ($tx->items as $item)
                                <tr>
                                    <td>{{ $item->category_name_snapshot }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        {{ rtrim(rtrim(number_format((float) $item->quantity, 3, ',', '.'), '0'), ',') }}
                                        {{ $item->unit_snapshot }}
                                    </td>
                                    <td class="text-right whitespace-nowrap text-base-content/60">
                                        @ Rp {{ number_format((float) $item->price_per_unit_snapshot, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right whitespace-nowrap font-medium">
                                        Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body text-center text-base-content/60 py-16">
                    {{ __('Belum ada transaksi nabung.') }}
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $this->transactions->links() }}</div>
</section>
