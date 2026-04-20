<?php

use App\Models\PointCashOut;
use App\Models\PointRule;
use App\Models\User;
use App\Services\PointCashOutService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Tukar Poin ke Saldo')] class extends Component {
    use Toast, WithPagination;

    public ?int $user_id = null;

    public string $points_used = '';

    public string $notes = '';

    public bool $formModal = false;

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'class' => 'w-16'],
            ['key' => 'cashed_out_at_label', 'label' => __('Tanggal'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'user_label', 'label' => __('Member'), 'sortable' => false],
            ['key' => 'points_used_label', 'label' => __('Poin'), 'class' => 'w-28', 'sortable' => false],
            ['key' => 'rate_label', 'label' => __('Rate'), 'class' => 'hidden lg:table-cell w-40', 'sortable' => false],
            ['key' => 'cash_amount_label', 'label' => __('Saldo Masuk'), 'sortable' => false],
        ];
    }

    #[Computed]
    public function cashOuts()
    {
        return PointCashOut::query()
            ->with('user:id,name,email')
            ->orderByDesc('cashed_out_at')
            ->orderByDesc('id')
            ->paginate(15);
    }

    #[Computed]
    public function memberOptions(): array
    {
        return User::nasabah()
            ->where('is_member', true)
            ->with('balance')
            ->whereHas('balance', fn ($q) => $q->where('points', '>', 0))
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name.' — '.number_format((int) ($u->balance->points ?? 0), 0, ',', '.').' '.__('poin'),
                'points' => (int) ($u->balance->points ?? 0),
            ])
            ->toArray();
    }

    #[Computed]
    public function activeRule(): ?PointRule
    {
        return PointRule::resolveActive();
    }

    #[Computed]
    public function preview(): array
    {
        $rule = $this->activeRule;
        $rate = $rule ? (float) $rule->rupiah_per_point : 0;
        $points = (int) ($this->points_used !== '' ? $this->points_used : 0);
        $cash = $points > 0 ? round($points * $rate, 2) : 0;

        return [
            'rate' => $rate,
            'points' => $points,
            'cash' => $cash,
        ];
    }

    #[Computed]
    public function selectedUser(): ?User
    {
        return $this->user_id ? User::with('balance')->find($this->user_id) : null;
    }

    public function startCreating(): void
    {
        $this->reset(['user_id', 'points_used', 'notes']);
        $this->resetErrorBag();
        $this->formModal = true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'points_used' => ['required', 'integer', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(PointCashOutService $service): void
    {
        $this->validate();

        $nasabah = User::nasabah()->findOrFail($this->user_id);

        try {
            $service->create(
                nasabah: $nasabah,
                pointsUsed: (int) $this->points_used,
                processedBy: Auth::user(),
                notes: $this->notes !== '' ? $this->notes : null,
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->success(__('Tukar poin ke saldo tersimpan.'));
        $this->formModal = false;
        unset($this->cashOuts, $this->memberOptions);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Tukar Poin ke Saldo') }}"
        subtitle="{{ __('Konversi poin member menjadi saldo tersedia — langsung bisa dicairkan lewat menu Pencairan.') }}"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                label="{{ __('Tukar Poin Baru') }}"
                wire:click="startCreating"
                data-test="cash-out-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    @if ($this->activeRule)
        <div class="mb-4 rounded-xl border border-accent/20 bg-accent/5 p-4 text-sm">
            <span class="font-semibold">{{ __('Rate aktif:') }}</span>
            {{ __('1 poin = Rp :rp', ['rp' => number_format((float) $this->activeRule->rupiah_per_point, 0, ',', '.')]) }}
            <span class="text-base-content/70">· {{ __('Berlaku sejak') }} {{ $this->activeRule->effective_from->format('d M Y') }}</span>
        </div>
    @else
        <div class="mb-4 rounded-xl border border-warning/30 bg-warning/10 p-4 text-sm">
            {{ __('Belum ada aturan poin aktif. Buat aturan di menu Master Poin sebelum memproses tukar poin.') }}
        </div>
    @endif

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->cashOuts"
        with-pagination
        striped
    >
        @scope('cell_cashed_out_at_label', $row)
            {{ $row->cashed_out_at->format('d M Y H:i') }}
        @endscope

        @scope('cell_user_label', $row)
            <div class="font-medium">{{ $row->user?->name ?? '—' }}</div>
            <div class="text-xs text-base-content/60">{{ $row->user?->email }}</div>
        @endscope

        @scope('cell_points_used_label', $row)
            <span class="font-semibold text-error">
                −{{ number_format($row->points_used, 0, ',', '.') }}
            </span>
        @endscope

        @scope('cell_rate_label', $row)
            <span class="text-xs text-base-content/70">
                1 poin = Rp {{ number_format((float) $row->rate_snapshot, 0, ',', '.') }}
            </span>
        @endscope

        @scope('cell_cash_amount_label', $row)
            <span class="font-semibold text-success">
                +Rp {{ number_format((float) $row->cash_amount, 0, ',', '.') }}
            </span>
        @endscope
    </x-mary-table>

    <x-mary-modal
        wire:model="formModal"
        title="{{ __('Tukar Poin ke Saldo') }}"
        subtitle="{{ __('Hanya member dengan poin > 0 yang muncul di dropdown.') }}"
        separator
        box-class="max-w-lg"
    >
        <x-mary-form wire:submit="save" no-separator>
            <x-mary-select
                wire:model.live="user_id"
                label="{{ __('Member') }}"
                :options="$this->memberOptions"
                option-label="name"
                option-value="id"
                placeholder="{{ __('Pilih member') }}"
                icon="o-user"
                required
            />

            @if ($this->selectedUser)
                <div class="rounded-lg bg-base-200 p-3 text-sm grid grid-cols-2 gap-2">
                    <div>
                        <div class="text-xs text-base-content/60">{{ __('Poin sekarang') }}</div>
                        <div class="font-semibold">
                            {{ number_format((int) ($this->selectedUser->balance?->points ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-base-content/60">{{ __('Saldo tersedia') }}</div>
                        <div class="font-semibold">
                            Rp {{ number_format((float) ($this->selectedUser->balance?->saldo_tersedia ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            @endif

            <x-mary-input
                wire:model.live.debounce.300ms="points_used"
                label="{{ __('Poin yang ditukar') }}"
                icon="o-sparkles"
                type="number"
                min="1"
                required
            />

            @if ($this->preview['rate'] > 0 && $this->preview['points'] > 0)
                <div role="status" aria-live="polite" class="rounded-lg border border-success/30 bg-success/10 p-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/70">{{ __('Saldo yang akan diterima') }}</span>
                        <span class="font-bold text-success-content dark:text-success text-lg">
                            Rp {{ number_format($this->preview['cash'], 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="text-xs text-base-content/60 mt-1">
                        {{ $this->preview['points'] }} poin × Rp {{ number_format($this->preview['rate'], 0, ',', '.') }}
                    </div>
                </div>
            @endif

            <x-mary-textarea wire:model="notes" label="{{ __('Catatan (opsional)') }}" rows="2" />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button
                    type="submit"
                    label="{{ __('Simpan') }}"
                    class="btn-primary"
                    spinner="save"
                    data-test="cash-out-save-button"
                />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</section>
