<?php

use App\Models\PointHistory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Histori Poin')] class extends Component {
    use WithPagination;

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'created_at_label', 'label' => __('Tanggal'), 'sortable' => false],
            ['key' => 'type_label', 'label' => __('Jenis'), 'sortable' => false],
            ['key' => 'points_label', 'label' => __('Poin'), 'sortable' => false],
            ['key' => 'balance_after_label', 'label' => __('Saldo Poin'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'description', 'label' => __('Keterangan'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function histories()
    {
        return PointHistory::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function currentPoints(): int
    {
        return (int) (Auth::user()->balance?->points ?? 0);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Histori Poin') }}"
        subtitle="{{ __('Riwayat perolehan dan penggunaan poin Anda.') }}"
        separator
    />

    <div class="card bg-primary/10 border border-primary/30 mb-4">
        <div class="card-body p-4 flex-row items-center justify-between">
            <div>
                <div class="text-sm text-base-content/70">{{ __('Poin saat ini') }}</div>
                <div class="text-3xl font-bold text-primary">{{ number_format($this->currentPoints, 0, ',', '.') }}</div>
            </div>
            <x-mary-icon name="o-sparkles" class="size-12 text-primary" />
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

        @scope('cell_type_label', $row)
            @if ($row->type === 'earn')
                <x-mary-badge value="{{ __('Dapat') }}" class="badge-success badge-soft" />
            @elseif ($row->type === 'redeem')
                <x-mary-badge value="{{ __('Tukar') }}" class="badge-warning badge-soft" />
            @else
                <x-mary-badge value="{{ __('Penyesuaian') }}" class="badge-ghost" />
            @endif
        @endscope

        @scope('cell_points_label', $row)
            <span @class([
                'font-semibold',
                'text-success' => $row->points > 0,
                'text-error' => $row->points < 0,
            ])>
                {{ $row->points > 0 ? '+' : '' }}{{ number_format($row->points, 0, ',', '.') }}
            </span>
        @endscope

        @scope('cell_balance_after_label', $row)
            {{ number_format($row->balance_after, 0, ',', '.') }}
        @endscope

        @scope('cell_description', $row)
            <span class="text-sm text-base-content/70">{{ $row->description ?? '—' }}</span>
        @endscope
    </x-mary-table>
</section>
