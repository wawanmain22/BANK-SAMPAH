<?php

use App\Models\Balance;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Release Saldo')] class extends Component {
    use Toast, WithPagination;

    public string $search = '';

    public ?int $releasingUserId = null;

    public string $amount = '';

    public string $notes = '';

    public bool $releaseModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('Nasabah'), 'sortable' => false],
            ['key' => 'tertahan_label', 'label' => __('Saldo Tertahan'), 'sortable' => false],
            ['key' => 'tersedia_label', 'label' => __('Saldo Tersedia'), 'class' => 'hidden md:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function nasabahList()
    {
        return User::nasabah()
            ->with('balance')
            ->whereHas('balance', fn ($q) => $q->where('saldo_tertahan', '>', 0))
            ->when($this->search !== '', function ($q) {
                $like = '%'.$this->search.'%';
                $q->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function releasingUser(): ?User
    {
        return $this->releasingUserId
            ? User::with('balance')->find($this->releasingUserId)
            : null;
    }

    public function startRelease(int $userId): void
    {
        $this->releasingUserId = $userId;
        $this->amount = (string) (Balance::where('user_id', $userId)->value('saldo_tertahan') ?? '');
        $this->notes = '';
        $this->resetErrorBag();
        $this->releaseModal = true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(BalanceService $service): void
    {
        $this->validate();

        if (! $this->releasingUserId) {
            return;
        }

        $nasabah = User::nasabah()->findOrFail($this->releasingUserId);

        try {
            $service->release(
                $nasabah,
                (float) $this->amount,
                Auth::user(),
                $this->notes !== '' ? $this->notes : null,
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->success(__('Saldo berhasil dipindahkan ke tersedia.'));
        $this->releaseModal = false;
        $this->reset(['releasingUserId', 'amount', 'notes']);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Release Saldo') }}"
        subtitle="{{ __('Pindahkan saldo nasabah dari tertahan menjadi tersedia ketika dana dari mitra sudah diterima.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                placeholder="{{ __('Cari nasabah...') }}"
                clearable
            />
        </x-slot:middle>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->nasabahList"
        with-pagination
        striped
    >
        @scope('cell_name', $row)
            <div>
                <div class="font-medium">{{ $row->name }}</div>
                <div class="text-xs text-base-content/60">{{ $row->email }}</div>
            </div>
        @endscope

        @scope('cell_tertahan_label', $row)
            <span class="font-semibold text-warning">
                Rp {{ number_format((float) $row->balance->saldo_tertahan, 2, ',', '.') }}
            </span>
        @endscope

        @scope('cell_tersedia_label', $row)
            Rp {{ number_format((float) $row->balance->saldo_tersedia, 2, ',', '.') }}
        @endscope

        @scope('actions', $row)
            <x-mary-button
                icon="o-arrow-right-circle"
                label="{{ __('Release') }}"
                wire:click="startRelease({{ $row->id }})"
                class="btn-primary btn-sm"
                data-test="release-{{ $row->id }}"
            />
        @endscope
    </x-mary-table>

    <x-mary-modal
        wire:model="releaseModal"
        title="{{ __('Release Saldo Tertahan') }}"
        :subtitle="$this->releasingUser?->name"
        separator
        box-class="max-w-md"
    >
        @if ($this->releasingUser)
            <div class="space-y-2 text-sm mb-4">
                <div class="flex justify-between">
                    <span class="text-base-content/60">{{ __('Saldo tertahan') }}</span>
                    <span class="font-semibold">
                        Rp {{ number_format((float) $this->releasingUser->balance->saldo_tertahan, 2, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-base-content/60">{{ __('Saldo tersedia') }}</span>
                    <span>
                        Rp {{ number_format((float) $this->releasingUser->balance->saldo_tersedia, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        @endif

        <x-mary-form wire:submit="save" no-separator>
            <x-mary-input
                wire:model="amount"
                label="{{ __('Jumlah yang di-release (Rp)') }}"
                type="number"
                step="0.01"
                min="0"
                icon="o-banknotes"
                required
            />
            <x-mary-textarea wire:model="notes" label="{{ __('Catatan (opsional)') }}" rows="2" />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.releaseModal = false" />
                <x-mary-button
                    type="submit"
                    label="{{ __('Release') }}"
                    class="btn-primary"
                    spinner="save"
                    data-test="release-save-button"
                />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</section>
