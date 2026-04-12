<?php

use App\Models\PointHistory;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Histori Poin')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?int $user_id = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'created_at_label', 'label' => __('Tanggal'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'user_name', 'label' => __('Nasabah'), 'sortable' => false],
            ['key' => 'type_label', 'label' => __('Jenis'), 'sortable' => false],
            ['key' => 'points_label', 'label' => __('Poin'), 'sortable' => false],
            ['key' => 'balance_after_label', 'label' => __('Saldo Poin'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
            ['key' => 'description', 'label' => __('Keterangan'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function histories()
    {
        return PointHistory::query()
            ->with('user:id,name,email')
            ->when($this->user_id, fn ($q) => $q->where('user_id', $this->user_id))
            ->when($this->search !== '', function ($q) {
                $q->whereHas('user', function ($q) {
                    $like = '%'.$this->search.'%';
                    $q->where('name', 'like', $like)->orWhere('email', 'like', $like);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function memberOptions(): array
    {
        return User::nasabah()
            ->where('is_member', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->toArray();
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Histori Poin') }}"
        subtitle="{{ __('Riwayat perolehan dan penggunaan poin nasabah member.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <div class="flex flex-col gap-2 md:flex-row">
                <x-mary-select
                    wire:model.live="user_id"
                    :options="$this->memberOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="{{ __('Semua member') }}"
                    icon="o-user"
                    class="md:w-56"
                />
                <x-mary-input
                    wire:model.live.debounce.300ms="search"
                    icon="o-magnifying-glass"
                    placeholder="{{ __('Cari nasabah...') }}"
                    clearable
                />
            </div>
        </x-slot:middle>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->histories"
        with-pagination
        striped
    >
        @scope('cell_created_at_label', $row)
            {{ $row->created_at->format('d M Y H:i') }}
        @endscope

        @scope('cell_user_name', $row)
            <div>
                <div class="font-medium">{{ $row->user?->name ?? '—' }}</div>
                <div class="text-xs text-base-content/60">{{ $row->user?->email }}</div>
            </div>
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
