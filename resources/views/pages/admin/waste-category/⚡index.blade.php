<?php

use App\Concerns\WasteCategoryValidationRules;
use App\Models\WasteCategory;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Kategori Sampah')] class extends Component {
    use Toast, WasteCategoryValidationRules, WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $unit = 'kg';

    public bool $is_active = true;

    public ?int $deletingId = null;

    public bool $formModal = false;

    public bool $deleteModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('Nama')],
            ['key' => 'unit', 'label' => __('Satuan'), 'class' => 'hidden md:table-cell'],
            ['key' => 'prices_count', 'label' => __('Riwayat'), 'class' => 'hidden lg:table-cell'],
            ['key' => 'status_label', 'label' => __('Status'), 'sortable' => false],
        ];
    }

    #[Computed]
    public function categories()
    {
        return WasteCategory::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->withCount('prices')
            ->orderBy('name')
            ->paginate(15);
    }

    public function rules(): array
    {
        return $this->wasteCategoryRules($this->editingId);
    }

    public function startCreating(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    public function startEditing(int $id): void
    {
        $category = WasteCategory::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = (string) ($category->description ?? '');
        $this->unit = $category->unit;
        $this->is_active = (bool) $category->is_active;

        $this->formModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $category = WasteCategory::findOrFail($this->editingId);
            $category->update($validated);
            $this->success(__('Kategori berhasil diperbarui.'));
        } else {
            WasteCategory::create([
                ...$validated,
                'slug' => Str::slug($validated['name']).'-'.Str::random(4),
            ]);
            $this->success(__('Kategori berhasil ditambahkan.'));
        }

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

        WasteCategory::findOrFail($this->deletingId)->delete();
        $this->deletingId = null;
        $this->deleteModal = false;

        $this->success(__('Kategori dihapus.'));
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'unit', 'is_active']);
        $this->is_active = true;
        $this->unit = 'kg';
        $this->resetErrorBag();
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Kategori Sampah') }}"
        subtitle="{{ __('Kelola kategori sampah yang diterima Bank Sampah.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                placeholder="{{ __('Cari kategori...') }}"
                clearable
            />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                wire:click="startCreating"
                label="{{ __('Tambah Kategori') }}"
                data-test="category-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->categories"
        with-pagination
        striped
    >
        @scope('cell_name', $row)
            <div>
                <div class="font-medium">{{ $row->name }}</div>
                @if ($row->description)
                    <div class="text-xs text-base-content/60 line-clamp-1">{{ $row->description }}</div>
                @endif
            </div>
        @endscope

        @scope('cell_prices_count', $row)
            {{ $row->prices_count }} {{ __('harga') }}
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
                    icon="o-pencil-square"
                    wire:click="startEditing({{ $row->id }})"
                    class="btn-ghost btn-sm"
                    data-test="category-edit-{{ $row->id }}"
                />
                <x-mary-button
                    icon="o-trash"
                    wire:click="confirmDelete({{ $row->id }})"
                    class="btn-ghost btn-sm text-error"
                    data-test="category-delete-{{ $row->id }}"
                />
            </div>
        @endscope
    </x-mary-table>

    <x-mary-modal
        wire:model="formModal"
        title="{{ $editingId ? __('Edit Kategori') : __('Tambah Kategori') }}"
        subtitle="{{ __('Pastikan nama kategori unik dan jelas.') }}"
        separator
        box-class="max-w-lg"
    >
        <x-mary-form wire:submit="save" no-separator>
            <x-mary-input wire:model="name" label="{{ __('Nama kategori') }}" icon="o-tag" required />
            <x-mary-textarea wire:model="description" label="{{ __('Deskripsi') }}" rows="2" />
            <x-mary-input wire:model="unit" label="{{ __('Satuan') }}" icon="o-scale" placeholder="kg" required />
            <x-mary-toggle wire:model="is_active" label="{{ __('Aktif') }}" right />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button
                    label="{{ __('Simpan') }}"
                    class="btn-primary"
                    type="submit"
                    spinner="save"
                    data-test="category-save-button"
                />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="deleteModal" title="{{ __('Hapus Kategori') }}" box-class="max-w-md">
        <p class="text-sm text-base-content/70">
            {{ __('Semua riwayat harga terkait akan ikut terhapus. Transaksi lama tetap menyimpan harga saat itu.') }}
        </p>

        <x-slot:actions>
            <x-mary-button label="{{ __('Batal') }}" @click="$wire.deleteModal = false" />
            <x-mary-button
                label="{{ __('Hapus') }}"
                class="btn-error"
                wire:click="delete"
                spinner
                data-test="category-confirm-delete"
            />
        </x-slot:actions>
    </x-mary-modal>
</section>
