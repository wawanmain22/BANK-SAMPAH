<?php

use App\Concerns\ProductValidationRules;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Produk')] class extends Component {
    use ProductValidationRules, Toast, WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $image = '';

    public string $unit = 'pcs';

    public string $price = '';

    public string $points_cost = '0';

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
            ['key' => 'name', 'label' => __('Produk'), 'sortable' => false],
            ['key' => 'unit', 'label' => __('Satuan'), 'class' => 'hidden md:table-cell'],
            ['key' => 'price_label', 'label' => __('Harga'), 'sortable' => false],
            ['key' => 'points_cost_label', 'label' => __('Poin Tukar'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'stock_label', 'label' => __('Stok'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
            ['key' => 'status_label', 'label' => __('Status'), 'sortable' => false],
        ];
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(15);
    }

    public function rules(): array
    {
        return $this->productRules($this->editingId);
    }

    public function startCreating(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    public function startEditing(int $id): void
    {
        $product = Product::findOrFail($id);

        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->description = (string) ($product->description ?? '');
        $this->image = (string) ($product->image ?? '');
        $this->unit = $product->unit;
        $this->price = (string) $product->price;
        $this->points_cost = (string) (int) $product->points_cost;
        $this->is_active = (bool) $product->is_active;

        $this->formModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            Product::findOrFail($this->editingId)->update($validated);
            $this->success(__('Produk berhasil diperbarui.'));
        } else {
            Product::create([
                ...$validated,
                'slug' => Str::slug($validated['name']).'-'.Str::random(4),
            ]);
            $this->success(__('Produk berhasil ditambahkan.'));
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

        try {
            Product::findOrFail($this->deletingId)->delete();
            $this->success(__('Produk dihapus.'));
        } catch (\Illuminate\Database\QueryException) {
            $this->error(__('Produk tidak bisa dihapus karena masih terkait dengan transaksi pengolahan.'));
        }

        $this->deletingId = null;
        $this->deleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'image', 'price']);
        $this->points_cost = '0';
        $this->unit = 'pcs';
        $this->is_active = true;
        $this->resetErrorBag();
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Produk Hasil Olahan') }}"
        subtitle="{{ __('Master data produk seperti paving block, kompos, kursi, pakan ternak. Set harga jual + poin tukar per produk.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                placeholder="{{ __('Cari produk...') }}"
                clearable
            />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                label="{{ __('Tambah Produk') }}"
                wire:click="startCreating"
                data-test="product-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->products"
        with-pagination
        striped
    >
        @scope('cell_name', $row)
            <div class="font-medium">{{ $row->name }}</div>
            @if ($row->description)
                <div class="text-xs text-base-content/60 line-clamp-1">{{ $row->description }}</div>
            @endif
        @endscope

        @scope('cell_price_label', $row)
            <span class="font-medium">Rp {{ number_format((float) $row->price, 0, ',', '.') }}</span>
        @endscope

        @scope('cell_points_cost_label', $row)
            @if ((int) $row->points_cost > 0)
                <span class="font-semibold text-accent">{{ number_format((int) $row->points_cost, 0, ',', '.') }} poin</span>
            @else
                <span class="text-base-content/40" aria-label="{{ __('Belum di-set') }}">—</span>
            @endif
        @endscope

        @scope('cell_stock_label', $row)
            {{ rtrim(rtrim(number_format((float) $row->stock, 3, ',', '.'), '0'), ',') }}
            <span class="text-xs text-base-content/60">{{ $row->unit }}</span>
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
                <x-mary-button icon="o-pencil-square" wire:click="startEditing({{ $row->id }})" class="btn-ghost btn-sm" />
                <x-mary-button icon="o-trash" wire:click="confirmDelete({{ $row->id }})" class="btn-ghost btn-sm text-error" />
            </div>
        @endscope
    </x-mary-table>

    <x-mary-modal
        wire:model="formModal"
        title="{{ $editingId ? __('Edit Produk') : __('Tambah Produk') }}"
        separator
        box-class="max-w-xl"
    >
        <x-mary-form wire:submit="save" no-separator>
            <x-mary-input wire:model="name" label="{{ __('Nama produk') }}" icon="o-cube" required />
            <x-mary-textarea wire:model="description" label="{{ __('Deskripsi') }}" rows="2" />
            <x-mary-input wire:model="image" label="{{ __('URL gambar produk') }}" icon="o-photo" placeholder="/images/demo/merchandise.jpg" />
            <div class="grid gap-3 md:grid-cols-3">
                <x-mary-input wire:model="unit" label="{{ __('Satuan') }}" icon="o-scale" placeholder="pcs" required />
                <x-mary-input wire:model="price" label="{{ __('Harga jual') }}" type="number" step="0.01" min="0" prefix="Rp" required />
                <x-mary-input
                    wire:model="points_cost"
                    label="{{ __('Poin untuk Tukar') }}"
                    icon="o-sparkles"
                    type="number"
                    min="0"
                    hint="{{ __('Poin member per 1 unit. 0 = tidak bisa ditukar poin.') }}"
                    required
                />
            </div>
            <x-mary-toggle wire:model="is_active" label="{{ __('Aktif') }}" right />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button type="submit" label="{{ __('Simpan') }}" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="deleteModal" title="{{ __('Hapus Produk') }}" box-class="max-w-md">
        <p class="text-sm text-base-content/70">
            {{ __('Produk tidak bisa dihapus jika sudah pernah dipakai di transaksi pengolahan.') }}
        </p>

        <x-slot:actions>
            <x-mary-button label="{{ __('Batal') }}" @click="$wire.deleteModal = false" />
            <x-mary-button label="{{ __('Hapus') }}" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</section>
