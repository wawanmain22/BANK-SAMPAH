<?php

use App\Models\User;
use App\Models\WasteCategory;
use App\Services\SedekahTransactionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Sedekah Baru')] class extends Component {
    use Toast;

    public ?int $user_id = null;

    public string $donor_name = '';

    public string $notes = '';

    /** @var array<int, array{waste_category_id: ?int, quantity: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->items = [['waste_category_id' => null, 'quantity' => '']];
    }

    #[Computed]
    public function nasabahOptions(): array
    {
        return User::nasabah()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name.' ('.$u->email.')'])
            ->toArray();
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return WasteCategory::active()
            ->orderBy('name')
            ->get(['id', 'name', 'unit'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'unit' => $c->unit])
            ->toArray();
    }

    #[Computed]
    public function totalWeight(): float
    {
        return (float) collect($this->items)->sum(fn ($i) => (float) ($i['quantity'] ?? 0));
    }

    public function addItem(): void
    {
        $this->items[] = ['waste_category_id' => null, 'quantity' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'donor_name' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.waste_category_id' => ['required', 'integer', 'exists:waste_categories,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function save(SedekahTransactionService $service): void
    {
        $this->validate();

        $donor = $this->user_id ? User::nasabah()->find($this->user_id) : null;

        try {
            $transaction = $service->create(
                items: array_map(fn ($item) => [
                    'waste_category_id' => (int) $item['waste_category_id'],
                    'quantity' => (float) $item['quantity'],
                ], $this->items),
                donor: $donor,
                donorName: $this->donor_name !== '' ? $this->donor_name : null,
                notes: $this->notes !== '' ? $this->notes : null,
                createdBy: Auth::user(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->success(__('Transaksi sedekah #:id tersimpan.', ['id' => $transaction->id]));
        $this->redirect(route('admin.sedekah.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Sedekah Baru') }}"
        subtitle="{{ __('Pilih donor (opsional jika anonim) dan input jenis sampah yang disumbangkan.') }}"
        separator
    >
        <x-slot:actions>
            <x-mary-button label="{{ __('Batal') }}" icon="o-arrow-uturn-left" link="{{ route('admin.sedekah.index') }}" />
        </x-slot:actions>
    </x-mary-header>

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <x-mary-select
                wire:model="user_id"
                label="{{ __('Donor nasabah (opsional)') }}"
                :options="$this->nasabahOptions"
                option-label="name"
                option-value="id"
                placeholder="{{ __('Pilih nasabah') }}"
                icon="o-user"
            />
            <x-mary-input
                wire:model="donor_name"
                label="{{ __('Nama donor (jika non-nasabah)') }}"
                icon="o-identification"
                placeholder="{{ __('Kosongkan jika anonim') }}"
            />
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold">{{ __('Item sampah') }}</h3>
                <x-mary-button
                    icon="o-plus"
                    label="{{ __('Tambah item') }}"
                    wire:click="addItem"
                    type="button"
                    class="btn-sm btn-ghost"
                />
            </div>

            @foreach ($items as $index => $item)
                @php
                    $categoryOpt = collect($this->categoryOptions)->firstWhere('id', $item['waste_category_id']);
                @endphp
                <div wire:key="sitem-{{ $index }}" class="card bg-base-100 border border-base-300">
                    <div class="card-body p-4 gap-3">
                        <div class="grid gap-3 md:grid-cols-12">
                            <div class="md:col-span-7">
                                <x-mary-select
                                    wire:model.live="items.{{ $index }}.waste_category_id"
                                    label="{{ __('Kategori') }}"
                                    :options="$this->categoryOptions"
                                    option-label="name"
                                    option-value="id"
                                    placeholder="{{ __('Pilih kategori') }}"
                                    icon="o-tag"
                                />
                            </div>
                            <div class="md:col-span-3">
                                <x-mary-input
                                    wire:model.live="items.{{ $index }}.quantity"
                                    label="{{ __('Berat') }}"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    :suffix="$categoryOpt['unit'] ?? 'kg'"
                                />
                            </div>
                            <div class="md:col-span-2 flex md:items-end">
                                <x-mary-button
                                    icon="o-trash"
                                    wire:click="removeItem({{ $index }})"
                                    type="button"
                                    class="btn-ghost btn-sm w-full text-error"
                                    label="{{ __('Hapus') }}"
                                />
                            </div>
                        </div>

                        @error("items.{$index}.waste_category_id")
                            <div class="text-sm text-error">{{ $message }}</div>
                        @enderror
                        @error("items.{$index}.quantity")
                            <div class="text-sm text-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <x-mary-textarea wire:model="notes" label="{{ __('Catatan (opsional)') }}" rows="2" />

        <div class="rounded-xl border border-base-300 bg-base-100 p-4 flex items-center justify-between">
            <div class="text-sm text-base-content/70">{{ __('Total berat') }}</div>
            <div class="text-lg font-bold">{{ number_format($this->totalWeight, 3, ',', '.') }} kg</div>
        </div>

        <div class="flex justify-end gap-2">
            <x-mary-button label="{{ __('Batal') }}" link="{{ route('admin.sedekah.index') }}" />
            <x-mary-button
                type="submit"
                label="{{ __('Simpan Sedekah') }}"
                class="btn-primary"
                spinner="save"
                data-test="sedekah-save-button"
            />
        </div>
    </form>
</section>
