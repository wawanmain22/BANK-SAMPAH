<?php

use App\Concerns\WastePriceValidationRules;
use App\Models\WasteCategory;
use App\Models\WastePrice;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Harga Sampah')] class extends Component {
    use Toast, WastePriceValidationRules;

    public ?int $waste_category_id = null;

    public string $price_per_unit = '';

    public string $effective_from = '';

    public string $notes = '';

    public ?int $historyCategoryId = null;

    public bool $formModal = false;

    public bool $historyModal = false;

    public function mount(): void
    {
        $this->effective_from = now()->toDateString();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('Kategori')],
            ['key' => 'unit', 'label' => __('Satuan'), 'class' => 'hidden md:table-cell'],
            ['key' => 'current_price_label', 'label' => __('Harga Aktif'), 'sortable' => false],
            ['key' => 'effective_from_label', 'label' => __('Berlaku Sejak'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function categories()
    {
        return WasteCategory::query()
            ->active()
            ->with('currentPrice')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function historyCategory(): ?WasteCategory
    {
        if (! $this->historyCategoryId) {
            return null;
        }

        return WasteCategory::with(['prices' => fn ($q) => $q->orderByDesc('effective_from')->orderByDesc('id')])
            ->find($this->historyCategoryId);
    }

    public function rules(): array
    {
        return $this->wastePriceRules();
    }

    public function startSettingPrice(int $categoryId): void
    {
        $this->resetForm();
        $this->waste_category_id = $categoryId;
        $this->formModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        WastePrice::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        $this->formModal = false;
        $this->success(__('Harga baru tersimpan.'));
        $this->resetForm();
        unset($this->categories);
    }

    public function showHistory(int $categoryId): void
    {
        $this->historyCategoryId = $categoryId;
        $this->historyModal = true;
    }

    private function resetForm(): void
    {
        $this->reset(['waste_category_id', 'price_per_unit', 'notes']);
        $this->effective_from = now()->toDateString();
        $this->resetErrorBag();
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Harga Sampah') }}"
        subtitle="{{ __('Harga per satuan untuk tiap kategori. Harga baru berlaku sesuai tanggal efektif dan tidak mengubah transaksi yang sudah ada.') }}"
        separator
        progress-indicator
    />

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->categories"
        striped
    >
        @scope('cell_current_price_label', $row)
            @if ($row->currentPrice)
                <span class="font-medium">
                    Rp {{ number_format((float) $row->currentPrice->price_per_unit, 2, ',', '.') }}
                </span>
            @else
                <x-mary-badge value="{{ __('Belum ada') }}" class="badge-warning badge-soft" />
            @endif
        @endscope

        @scope('cell_effective_from_label', $row)
            {{ $row->currentPrice?->effective_from?->format('d M Y') ?? '—' }}
        @endscope

        @scope('actions', $row)
            <div class="flex items-center gap-1">
                <x-mary-button
                    icon="o-banknotes"
                    wire:click="startSettingPrice({{ $row->id }})"
                    class="btn-primary btn-sm"
                    label="{{ __('Set Harga') }}"
                    responsive
                    data-test="price-set-{{ $row->id }}"
                />
                <x-mary-button
                    icon="o-clock"
                    wire:click="showHistory({{ $row->id }})"
                    class="btn-ghost btn-sm"
                    label="{{ __('Riwayat') }}"
                    responsive
                    data-test="price-history-{{ $row->id }}"
                />
            </div>
        @endscope
    </x-mary-table>

    @if ($this->categories->isEmpty())
        <div class="rounded-xl border border-base-300 bg-base-100 p-8 text-center text-base-content/60">
            {{ __('Belum ada kategori aktif. Tambahkan dulu di menu Kategori Sampah.') }}
        </div>
    @endif

    <x-mary-modal
        wire:model="formModal"
        title="{{ __('Set Harga Baru') }}"
        subtitle="{{ __('Harga akan berlaku mulai tanggal efektif. Harga lama tetap tersimpan sebagai riwayat.') }}"
        separator
        box-class="max-w-lg"
    >
        <x-mary-form wire:submit="save" no-separator>
            <x-mary-input
                wire:model="price_per_unit"
                label="{{ __('Harga per satuan (Rp)') }}"
                icon="o-banknotes"
                type="number"
                step="0.01"
                min="0"
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

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button
                    label="{{ __('Simpan') }}"
                    class="btn-primary"
                    type="submit"
                    spinner="save"
                    data-test="price-save-button"
                />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal
        wire:model="historyModal"
        title="{{ __('Riwayat Harga') }}"
        subtitle="{{ $this->historyCategory?->name }}"
        separator
        box-class="max-w-xl"
    >
        @if ($this->historyCategory)
            <div class="max-h-[60vh] overflow-y-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>{{ __('Berlaku') }}</th>
                            <th>{{ __('Harga') }}</th>
                            <th>{{ __('Catatan') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->historyCategory->prices as $price)
                            <tr wire:key="price-{{ $price->id }}">
                                <td class="whitespace-nowrap">{{ $price->effective_from->format('d M Y') }}</td>
                                <td class="whitespace-nowrap">Rp {{ number_format((float) $price->price_per_unit, 2, ',', '.') }}</td>
                                <td class="text-sm text-base-content/70">{{ $price->notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-base-content/60">
                                    {{ __('Belum ada riwayat harga.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:actions>
            <x-mary-button label="{{ __('Tutup') }}" @click="$wire.historyModal = false" />
        </x-slot:actions>
    </x-mary-modal>
</section>
