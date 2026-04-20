<?php

use App\Concerns\WasteItemValidationRules;
use App\Concerns\WastePriceValidationRules;
use App\Models\WasteCategory;
use App\Models\WasteItem;
use App\Models\WastePrice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Barang Sampah')] class extends Component {
    use Toast, WasteItemValidationRules, WastePriceValidationRules, WithPagination;

    public string $search = '';

    public ?int $category_filter = null;

    public ?int $editingId = null;

    public ?int $waste_category_id = null;

    public string $code = '';

    public string $name = '';

    public string $unit = 'kg';

    public string $price_per_unit = '';

    public string $description = '';

    public bool $is_active = true;

    public ?int $deletingId = null;

    public bool $formModal = false;

    public bool $deleteModal = false;

    public ?int $historyItemId = null;

    public bool $historyModal = false;

    public ?int $priceItemId = null;

    public string $price_new = '';

    public string $price_effective_from = '';

    public string $price_notes = '';

    public bool $priceModal = false;

    public function mount(): void
    {
        $this->price_effective_from = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'code', 'label' => __('Kode'), 'class' => 'w-24'],
            ['key' => 'name', 'label' => __('Nama')],
            ['key' => 'category_label', 'label' => __('Kategori'), 'class' => 'hidden md:table-cell'],
            ['key' => 'unit', 'label' => __('Satuan'), 'class' => 'hidden md:table-cell'],
            ['key' => 'price_label', 'label' => __('Harga Aktif'), 'sortable' => false],
            ['key' => 'status_label', 'label' => __('Status'), 'sortable' => false],
        ];
    }

    #[Computed]
    public function items()
    {
        return WasteItem::query()
            ->with('category:id,name,code_prefix')
            ->when($this->search !== '', fn ($q) => $q->where(function ($inner) {
                $inner->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            }))
            ->when($this->category_filter, fn ($q) => $q->where('waste_category_id', $this->category_filter))
            ->orderBy('code')
            ->paginate(20);
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return WasteCategory::query()
            ->orderBy('code_prefix')
            ->get(['id', 'name', 'code_prefix'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => "{$c->code_prefix} — {$c->name}"])
            ->toArray();
    }

    #[Computed]
    public function historyItem(): ?WasteItem
    {
        if (! $this->historyItemId) {
            return null;
        }

        return WasteItem::with(['prices' => fn ($q) => $q->orderByDesc('effective_from')->orderByDesc('id')])
            ->find($this->historyItemId);
    }

    #[Computed]
    public function priceItem(): ?WasteItem
    {
        return $this->priceItemId ? WasteItem::find($this->priceItemId) : null;
    }

    public function rules(): array
    {
        return $this->wasteItemRules($this->editingId, includePrice: $this->editingId === null);
    }

    public function startCreating(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    public function startEditing(int $id): void
    {
        $item = WasteItem::findOrFail($id);

        $this->editingId = $item->id;
        $this->waste_category_id = $item->waste_category_id;
        $this->code = $item->code;
        $this->name = $item->name;
        $this->unit = $item->unit;
        $this->price_per_unit = (string) (float) $item->price_per_unit;
        $this->description = (string) ($item->description ?? '');
        $this->is_active = (bool) $item->is_active;

        $this->formModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $validated['code'] = strtoupper($validated['code']);

        DB::transaction(function () use ($validated) {
            if ($this->editingId) {
                $item = WasteItem::findOrFail($this->editingId);

                $item->update([
                    ...$validated,
                    'slug' => Str::slug($validated['name'].'-'.$validated['code']),
                ]);

                $this->success(__('Barang berhasil diperbarui.'));
            } else {
                $item = WasteItem::create([
                    ...$validated,
                    'slug' => Str::slug($validated['name'].'-'.$validated['code']),
                ]);

                WastePrice::create([
                    'waste_item_id' => $item->id,
                    'price_per_unit' => (float) $validated['price_per_unit'],
                    'effective_from' => now()->toDateString(),
                    'notes' => 'Harga awal saat barang dibuat.',
                    'created_by' => Auth::id(),
                ]);

                $this->success(__('Barang berhasil ditambahkan.'));
            }
        });

        $this->formModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->deleteModal = true;
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        try {
            WasteItem::findOrFail($this->deletingId)->delete();
            $this->success(__('Barang dihapus.'));
        } catch (\Illuminate\Database\QueryException $e) {
            $this->error(__('Tidak bisa hapus: barang masih dipakai di transaksi.'));
        }

        $this->deletingId = null;
        $this->deleteModal = false;
    }

    public function showHistory(int $id): void
    {
        $this->historyItemId = $id;
        $this->historyModal = true;
    }

    public function startSettingPrice(int $id): void
    {
        $this->resetPriceForm();
        $this->priceItemId = $id;
        $this->priceModal = true;
    }

    public function savePrice(): void
    {
        $validated = $this->validate([
            'priceItemId' => ['required', 'integer', \Illuminate\Validation\Rule::exists(WasteItem::class, 'id')],
            'price_new' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'price_effective_from' => ['required', 'date'],
            'price_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            WastePrice::create([
                'waste_item_id' => $validated['priceItemId'],
                'price_per_unit' => (float) $validated['price_new'],
                'effective_from' => $validated['price_effective_from'],
                'notes' => $validated['price_notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            WasteItem::whereKey($validated['priceItemId'])
                ->update(['price_per_unit' => (float) $validated['price_new']]);
        });

        $this->priceModal = false;
        $this->success(__('Harga baru tersimpan.'));
        $this->resetPriceForm();
        unset($this->items);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'waste_category_id', 'code', 'name', 'unit', 'price_per_unit', 'description', 'is_active']);
        $this->is_active = true;
        $this->unit = 'kg';
        $this->resetErrorBag();
    }

    private function resetPriceForm(): void
    {
        $this->reset(['priceItemId', 'price_new', 'price_notes']);
        $this->price_effective_from = now()->toDateString();
        $this->resetErrorBag();
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Barang Sampah') }}"
        subtitle="{{ __('Master barang + riwayat harga. Ubah harga via tombol Set Harga agar riwayat tercatat.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <div class="flex flex-col gap-2 md:flex-row">
                <x-mary-input
                    wire:model.live.debounce.300ms="search"
                    icon="o-magnifying-glass"
                    placeholder="{{ __('Cari barang / kode...') }}"
                    clearable
                    class="md:w-60"
                />
                <x-mary-select
                    wire:model.live="category_filter"
                    :options="$this->categoryOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="{{ __('Semua kategori') }}"
                    icon="o-tag"
                    class="md:w-56"
                />
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                wire:click="startCreating"
                label="{{ __('Tambah Barang') }}"
                data-test="item-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->items"
        with-pagination
        striped
    >
        @scope('cell_code', $row)
            <span class="font-mono text-sm">{{ $row->code }}</span>
        @endscope

        @scope('cell_name', $row)
            <div>
                <div class="font-medium">{{ $row->name }}</div>
                @if ($row->description)
                    <div class="text-xs text-base-content/60 line-clamp-1">{{ $row->description }}</div>
                @endif
            </div>
        @endscope

        @scope('cell_category_label', $row)
            <x-mary-badge value="{{ $row->category?->name }}" class="badge-neutral badge-soft" />
        @endscope

        @scope('cell_price_label', $row)
            @if ((float) $row->price_per_unit > 0)
                <span class="font-semibold">Rp {{ number_format((float) $row->price_per_unit, 2, ',', '.') }}</span>
                <span class="text-xs text-base-content/60">/{{ $row->unit }}</span>
            @else
                <x-mary-badge value="{{ __('Belum di-set') }}" class="badge-warning badge-soft" />
            @endif
        @endscope

        @scope('cell_status_label', $row)
            @if ($row->is_active)
                <x-mary-badge value="{{ __('Aktif') }}" class="badge-success badge-soft" />
            @else
                <x-mary-badge value="{{ __('Non-aktif') }}" class="badge-ghost" />
            @endif
        @endscope

        @scope('actions', $row)
            <div class="flex items-center gap-1">
                <x-mary-button
                    icon="o-banknotes"
                    wire:click="startSettingPrice({{ $row->id }})"
                    class="btn-ghost btn-sm text-success"
                    tooltip="{{ __('Set Harga') }}"
                    aria-label="{{ __('Set harga untuk').' '.$row->name }}"
                    data-test="item-set-price-{{ $row->id }}"
                />
                <x-mary-button
                    icon="o-clock"
                    wire:click="showHistory({{ $row->id }})"
                    class="btn-ghost btn-sm"
                    tooltip="{{ __('Riwayat harga') }}"
                    aria-label="{{ __('Riwayat harga').' '.$row->name }}"
                />
                <x-mary-button
                    icon="o-pencil-square"
                    wire:click="startEditing({{ $row->id }})"
                    class="btn-ghost btn-sm"
                    tooltip="{{ __('Edit barang') }}"
                    aria-label="{{ __('Edit').' '.$row->name }}"
                    data-test="item-edit-{{ $row->id }}"
                />
                <x-mary-button
                    icon="o-trash"
                    wire:click="confirmDelete({{ $row->id }})"
                    class="btn-ghost btn-sm text-error"
                    tooltip-left="{{ __('Hapus barang') }}"
                    aria-label="{{ __('Hapus').' '.$row->name }}"
                    data-test="item-delete-{{ $row->id }}"
                />
            </div>
        @endscope
    </x-mary-table>

    <x-mary-modal
        wire:model="formModal"
        title="{{ $editingId ? __('Edit Barang') : __('Tambah Barang') }}"
        subtitle="{{ $editingId ? __('Ubah metadata barang. Harga diatur lewat tombol Set Harga.') : __('Isi kategori, kode unik & harga awal. Riwayat harga tercatat otomatis.') }}"
        separator
        box-class="max-w-xl"
    >
        <x-mary-form wire:submit="save" no-separator>
            <x-mary-select
                wire:model="waste_category_id"
                label="{{ __('Kategori') }}"
                :options="$this->categoryOptions"
                option-label="name"
                option-value="id"
                placeholder="{{ __('Pilih kategori') }}"
                icon="o-tag"
                required
            />

            <div class="grid gap-3 md:grid-cols-4">
                <x-mary-input
                    wire:model="code"
                    label="{{ __('Kode') }}"
                    icon="o-hashtag"
                    placeholder="KT1"
                    maxlength="16"
                    class="uppercase font-mono"
                    required
                />
                <div class="md:col-span-3">
                    <x-mary-input wire:model="name" label="{{ __('Nama barang') }}" placeholder="Dus / PET Botol Bersih / ..." required />
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <x-mary-input wire:model="unit" label="{{ __('Satuan') }}" icon="o-scale" placeholder="kg" required />
                @if (! $editingId)
                    <x-mary-input
                        wire:model="price_per_unit"
                        label="{{ __('Harga awal (Rp)') }}"
                        icon="o-banknotes"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                    />
                @endif
            </div>

            <x-mary-textarea wire:model="description" label="{{ __('Deskripsi (opsional)') }}" rows="2" />
            <x-mary-toggle wire:model="is_active" label="{{ __('Aktif') }}" right />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button
                    label="{{ __('Simpan') }}"
                    class="btn-primary"
                    type="submit"
                    spinner="save"
                    data-test="item-save-button"
                />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal
        wire:model="priceModal"
        title="{{ __('Set Harga Baru') }}"
        subtitle="{{ $this->priceItem?->code }} — {{ $this->priceItem?->name }}"
        separator
        box-class="max-w-lg"
    >
        <x-mary-form wire:submit="savePrice" no-separator>
            <x-mary-input
                wire:model="price_new"
                label="{{ __('Harga per satuan (Rp)') }}"
                icon="o-banknotes"
                type="number"
                step="0.01"
                min="0"
                required
            />
            <x-mary-input
                wire:model="price_effective_from"
                label="{{ __('Berlaku sejak') }}"
                icon="o-calendar"
                type="date"
                required
            />
            <x-mary-textarea
                wire:model="price_notes"
                label="{{ __('Catatan (opsional)') }}"
                placeholder="{{ __('Alasan perubahan harga...') }}"
                rows="2"
            />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.priceModal = false" />
                <x-mary-button
                    label="{{ __('Simpan Harga') }}"
                    class="btn-primary"
                    type="submit"
                    spinner="savePrice"
                    data-test="price-save-button"
                />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="deleteModal" title="{{ __('Hapus Barang') }}" box-class="max-w-md">
        <p class="text-sm text-base-content/70">
            {{ __('Hanya bisa dihapus jika barang belum pernah dipakai transaksi. Jika sudah, set status Non-aktif saja.') }}
        </p>

        <x-slot:actions>
            <x-mary-button label="{{ __('Batal') }}" @click="$wire.deleteModal = false" />
            <x-mary-button
                label="{{ __('Hapus') }}"
                class="btn-error"
                wire:click="delete"
                spinner
                data-test="item-confirm-delete"
            />
        </x-slot:actions>
    </x-mary-modal>

    <x-mary-modal
        wire:model="historyModal"
        title="{{ __('Riwayat Harga') }}"
        subtitle="{{ $this->historyItem?->code }} — {{ $this->historyItem?->name }}"
        separator
        box-class="max-w-xl"
    >
        @if ($this->historyItem)
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
                        @forelse ($this->historyItem->prices as $price)
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
