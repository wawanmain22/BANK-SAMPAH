<?php

use App\Concerns\PointRuleValidationRules;
use App\Models\PointRule;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Master Poin')] class extends Component {
    use PointRuleValidationRules, Toast, WithPagination;

    public string $points_per_rupiah = '';

    public string $rupiah_per_point = '';

    public string $effective_from = '';

    public string $notes = '';

    public bool $is_active = true;

    public bool $formModal = false;

    public function mount(): void
    {
        $this->effective_from = now()->toDateString();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'effective_from_label', 'label' => __('Berlaku Sejak')],
            ['key' => 'rate_label', 'label' => __('Konversi')],
            ['key' => 'created_by_label', 'label' => __('Dibuat oleh'), 'class' => 'hidden md:table-cell'],
            ['key' => 'notes_label', 'label' => __('Catatan'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
            ['key' => 'status_label', 'label' => __('Status'), 'sortable' => false],
        ];
    }

    #[Computed]
    public function rules_list()
    {
        return PointRule::query()
            ->with('createdBy:id,name')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->paginate(15);
    }

    #[Computed]
    public function activeRule(): ?PointRule
    {
        return PointRule::resolveActive();
    }

    public function rules(): array
    {
        return $this->pointRuleRules();
    }

    public function startCreating(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        PointRule::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        $this->formModal = false;
        $this->success(__('Aturan poin baru tersimpan.'));
        $this->resetForm();
        unset($this->rules_list, $this->activeRule);
    }

    public function toggleActive(int $id): void
    {
        $rule = PointRule::findOrFail($id);
        $rule->is_active = ! $rule->is_active;
        $rule->save();

        $this->success(__('Status aturan diperbarui.'));
        unset($this->rules_list, $this->activeRule);
    }

    private function resetForm(): void
    {
        $this->reset(['points_per_rupiah', 'rupiah_per_point', 'notes', 'is_active']);
        $this->is_active = true;
        $this->effective_from = now()->toDateString();
        $this->resetErrorBag();
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Master Poin') }}"
        subtitle="{{ __('Aturan konversi transaksi nabung → poin nasabah. Setiap perubahan disimpan sebagai aturan baru; aturan lama tetap bisa dilihat di riwayat.') }}"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                wire:click="startCreating"
                label="{{ __('Aturan Baru') }}"
                data-test="point-rule-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    @if ($this->activeRule)
        <div class="mb-4 grid gap-3 md:grid-cols-2">
            <div class="rounded-xl border border-primary/20 bg-primary/5 p-4">
                <div class="text-xs uppercase tracking-wide text-primary/80">{{ __('Nabung → Poin') }}</div>
                <div class="mt-1 text-lg font-bold">
                    {{ __('Rp :rp = :p poin', ['rp' => number_format(1 / max((float) $this->activeRule->points_per_rupiah, 0.000001), 0, ',', '.'), 'p' => 1]) }}
                </div>
                <div class="text-sm text-base-content/70">
                    {{ __('Rate:') }} {{ rtrim(rtrim(number_format((float) $this->activeRule->points_per_rupiah, 6, ',', '.'), '0'), ',') }}
                </div>
            </div>
            <div class="rounded-xl border border-accent/20 bg-accent/5 p-4">
                <div class="text-xs uppercase tracking-wide text-accent/80">{{ __('Poin → Saldo') }}</div>
                <div class="mt-1 text-lg font-bold">
                    {{ __(':p poin = Rp :rp', ['p' => 1, 'rp' => number_format((float) $this->activeRule->rupiah_per_point, 0, ',', '.')]) }}
                </div>
                <div class="text-sm text-base-content/70">
                    {{ __('Berlaku sejak') }} {{ $this->activeRule->effective_from->format('d M Y') }}
                </div>
            </div>
        </div>
    @else
        <div class="mb-4 rounded-xl border border-warning/30 bg-warning/10 p-4 text-sm">
            {{ __('Belum ada aturan poin aktif — nasabah member tidak akan dapat poin sampai aturan dibuat.') }}
        </div>
    @endif

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->rules_list"
        with-pagination
        striped
    >
        @scope('cell_effective_from_label', $row)
            <span class="font-medium">{{ $row->effective_from->format('d M Y') }}</span>
        @endscope

        @scope('cell_rate_label', $row)
            <div class="text-xs text-base-content/70">
                <div>{{ __('Nabung:') }} Rp {{ number_format(1 / max((float) $row->points_per_rupiah, 0.000001), 0, ',', '.') }} = 1 poin</div>
                <div>{{ __('Cashout:') }} 1 poin = Rp {{ number_format((float) $row->rupiah_per_point, 0, ',', '.') }}</div>
            </div>
        @endscope

        @scope('cell_created_by_label', $row)
            <span class="text-sm">{{ $row->createdBy?->name ?? '—' }}</span>
        @endscope

        @scope('cell_notes_label', $row)
            <span class="text-sm text-base-content/70 line-clamp-2">{{ $row->notes ?? '—' }}</span>
        @endscope

        @scope('cell_status_label', $row)
            @if ($row->is_active)
                <x-mary-badge value="{{ __('Aktif') }}" class="badge-success badge-soft" />
            @else
                <x-mary-badge value="{{ __('Non-aktif') }}" class="badge-ghost" />
            @endif
        @endscope

        @scope('actions', $row)
            <x-mary-button
                icon="{{ $row->is_active ? 'o-pause-circle' : 'o-play-circle' }}"
                wire:click="toggleActive({{ $row->id }})"
                class="btn-ghost btn-sm"
                tooltip="{{ $row->is_active ? __('Non-aktifkan') : __('Aktifkan') }}"
                data-test="point-rule-toggle-{{ $row->id }}"
            />
        @endscope
    </x-mary-table>

    <x-mary-modal
        wire:model="formModal"
        title="{{ __('Aturan Poin Baru') }}"
        subtitle="{{ __('Aturan lama tetap tersimpan sebagai riwayat. Poin yang sudah dibagikan tidak akan berubah.') }}"
        separator
        box-class="max-w-lg"
    >
        <x-mary-form wire:submit="save" no-separator>
            <x-mary-input
                wire:model="points_per_rupiah"
                label="{{ __('Nabung → Poin (rate per Rupiah)') }}"
                icon="o-arrow-trending-up"
                type="number"
                step="0.000001"
                min="0"
                placeholder="0.001"
                hint="{{ __('Contoh 0.001 artinya setiap Rp 1.000 nabung = 1 poin.') }}"
                required
            />
            <x-mary-input
                wire:model="rupiah_per_point"
                label="{{ __('Poin → Saldo (Rupiah per Poin)') }}"
                icon="o-banknotes"
                type="number"
                step="0.01"
                min="0"
                placeholder="1000"
                hint="{{ __('Contoh 1000 artinya 1 poin ditukar balik jadi Rp 1.000 saldo.') }}"
                required
            />
            <x-mary-input
                wire:model="effective_from"
                label="{{ __('Berlaku sejak') }}"
                icon="o-calendar"
                type="date"
                required
            />
            <x-mary-textarea wire:model="notes" label="{{ __('Catatan (opsional)') }}" rows="2" />
            <x-mary-toggle wire:model="is_active" label="{{ __('Langsung aktif') }}" right />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button
                    label="{{ __('Simpan') }}"
                    class="btn-primary"
                    type="submit"
                    spinner="save"
                    data-test="point-rule-save-button"
                />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</section>
