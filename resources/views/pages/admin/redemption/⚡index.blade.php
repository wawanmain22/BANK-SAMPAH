<?php

use App\Models\Balance;
use App\Models\Product;
use App\Models\Redemption;
use App\Models\User;
use App\Services\RedemptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Tukar Poin')] class extends Component {
    use Toast, WithPagination;

    public ?int $user_id = null;

    public ?int $product_id = null;

    public string $quantity = '1';

    public string $points_used = '';

    public string $notes = '';

    public bool $formModal = false;

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'class' => 'w-16'],
            ['key' => 'redeemed_at_label', 'label' => __('Tanggal'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'user_name', 'label' => __('Member'), 'sortable' => false],
            ['key' => 'product_display', 'label' => __('Produk'), 'sortable' => false],
            ['key' => 'points_used', 'label' => __('Poin'), 'sortable' => false],
        ];
    }

    #[Computed]
    public function redemptions()
    {
        return Redemption::query()
            ->with('user:id,name,email')
            ->orderByDesc('redeemed_at')
            ->orderByDesc('id')
            ->paginate(15);
    }

    #[Computed]
    public function memberOptions(): array
    {
        return User::nasabah()
            ->where('is_member', true)
            ->with('balance')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name.' — '.number_format((int) ($u->balance->points ?? 0), 0, ',', '.').' '.__('poin'),
            ])
            ->toArray();
    }

    #[Computed]
    public function productOptions(): array
    {
        return Product::active()
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'stock'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name.' — '.__('stok').': '
                    .rtrim(rtrim(number_format((float) $p->stock, 3, ',', '.'), '0'), ',').' '.$p->unit,
            ])
            ->toArray();
    }

    #[Computed]
    public function selectedUser(): ?User
    {
        return $this->user_id ? User::with('balance')->find($this->user_id) : null;
    }

    public function startCreating(): void
    {
        $this->reset(['user_id', 'product_id', 'points_used', 'notes']);
        $this->quantity = '1';
        $this->resetErrorBag();
        $this->formModal = true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'points_used' => ['required', 'integer', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(RedemptionService $service): void
    {
        $this->validate();

        $nasabah = User::nasabah()->findOrFail($this->user_id);
        $product = Product::findOrFail($this->product_id);

        try {
            $service->create(
                nasabah: $nasabah,
                product: $product,
                quantity: (float) $this->quantity,
                pointsUsed: (int) $this->points_used,
                processedBy: Auth::user(),
                notes: $this->notes !== '' ? $this->notes : null,
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->success(__('Tukar poin tersimpan.'));
        $this->formModal = false;
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Tukar Poin') }}"
        subtitle="{{ __('Riwayat penukaran poin member menjadi produk olahan.') }}"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                label="{{ __('Tukar Poin Baru') }}"
                wire:click="startCreating"
                data-test="redemption-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->redemptions"
        with-pagination
        striped
    >
        @scope('cell_redeemed_at_label', $row)
            {{ $row->redeemed_at->format('d M Y H:i') }}
        @endscope

        @scope('cell_user_name', $row)
            <div class="font-medium">{{ $row->user?->name ?? '—' }}</div>
            <div class="text-xs text-base-content/60">{{ $row->user?->email }}</div>
        @endscope

        @scope('cell_product_display', $row)
            <div>
                {{ rtrim(rtrim(number_format((float) $row->quantity, 3, ',', '.'), '0'), ',') }}
                {{ $row->unit_snapshot }} {{ $row->product_name_snapshot }}
            </div>
        @endscope

        @scope('cell_points_used', $row)
            <span class="font-semibold text-error">−{{ number_format($row->points_used, 0, ',', '.') }}</span>
        @endscope
    </x-mary-table>

    <x-mary-modal
        wire:model="formModal"
        title="{{ __('Tukar Poin Baru') }}"
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
                <div class="rounded-lg bg-base-200 p-3 text-sm">
                    {{ __('Poin tersedia') }}:
                    <span class="font-semibold">
                        {{ number_format((int) ($this->selectedUser->balance?->points ?? 0), 0, ',', '.') }}
                    </span>
                </div>
            @endif

            <x-mary-select
                wire:model="product_id"
                label="{{ __('Produk') }}"
                :options="$this->productOptions"
                option-label="name"
                option-value="id"
                placeholder="{{ __('Pilih produk') }}"
                icon="o-cube"
                required
            />

            <div class="grid gap-3 md:grid-cols-2">
                <x-mary-input wire:model="quantity" label="{{ __('Jumlah') }}" type="number" step="0.001" min="0" required />
                <x-mary-input wire:model="points_used" label="{{ __('Poin yang ditukar') }}" type="number" min="1" required />
            </div>

            <x-mary-textarea wire:model="notes" label="{{ __('Catatan') }}" rows="2" />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button type="submit" label="{{ __('Simpan') }}" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</section>
