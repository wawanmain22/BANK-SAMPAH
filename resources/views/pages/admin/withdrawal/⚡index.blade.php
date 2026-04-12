<?php

use App\Models\Balance;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\BalanceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Pencairan') ] class extends Component {
    use Toast, WithPagination;

    public ?int $user_id = null;

    public string $amount = '';

    public string $method = 'cash';

    public string $bank_name = '';

    public string $account_number = '';

    public string $account_name = '';

    public string $notes = '';

    public bool $formModal = false;

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'class' => 'w-16'],
            ['key' => 'processed_at_label', 'label' => __('Tanggal'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'user_name', 'label' => __('Nasabah'), 'sortable' => false],
            ['key' => 'amount_label', 'label' => __('Jumlah'), 'sortable' => false],
            ['key' => 'method_label', 'label' => __('Metode'), 'sortable' => false],
        ];
    }

    #[Computed]
    public function withdrawals()
    {
        return WithdrawalRequest::query()
            ->with('user:id,name,email')
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->paginate(15);
    }

    #[Computed]
    public function nasabahOptions(): array
    {
        return User::nasabah()
            ->with('balance')
            ->whereHas('balance', fn ($q) => $q->where('saldo_tersedia', '>', 0))
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name.' — '.__('tersedia').' Rp '.number_format((float) $u->balance->saldo_tersedia, 0, ',', '.'),
            ])
            ->toArray();
    }

    #[Computed]
    public function selectedBalance(): ?Balance
    {
        return $this->user_id ? Balance::firstWhere('user_id', $this->user_id) : null;
    }

    public function startCreating(): void
    {
        $this->reset(['user_id', 'amount', 'bank_name', 'account_number', 'account_name', 'notes']);
        $this->method = 'cash';
        $this->resetErrorBag();
        $this->formModal = true;
    }

    public function rules(): array
    {
        $rules = [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'in:cash,transfer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->method === 'transfer') {
            $rules['bank_name'] = ['required', 'string', 'max:64'];
            $rules['account_number'] = ['required', 'string', 'max:32'];
            $rules['account_name'] = ['required', 'string', 'max:128'];
        }

        return $rules;
    }

    public function save(BalanceService $service): void
    {
        $this->validate();

        $nasabah = User::nasabah()->findOrFail($this->user_id);

        try {
            $service->withdraw(
                $nasabah,
                (float) $this->amount,
                $this->method,
                Auth::user(),
                meta: [
                    'bank_name' => $this->bank_name !== '' ? $this->bank_name : null,
                    'account_number' => $this->account_number !== '' ? $this->account_number : null,
                    'account_name' => $this->account_name !== '' ? $this->account_name : null,
                ],
                notes: $this->notes !== '' ? $this->notes : null,
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->success(__('Pencairan tersimpan.'));
        $this->formModal = false;
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Pencairan') }}"
        subtitle="{{ __('Pencairan saldo tersedia — cash atau transfer — yang sudah diproses admin.') }}"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                label="{{ __('Pencairan Baru') }}"
                wire:click="startCreating"
                data-test="withdrawal-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->withdrawals"
        with-pagination
        striped
    >
        @scope('cell_processed_at_label', $row)
            {{ $row->processed_at->format('d M Y H:i') }}
        @endscope

        @scope('cell_user_name', $row)
            <div>
                <div class="font-medium">{{ $row->user?->name ?? '—' }}</div>
                <div class="text-xs text-base-content/60">{{ $row->user?->email }}</div>
            </div>
        @endscope

        @scope('cell_amount_label', $row)
            <span class="font-semibold">Rp {{ number_format((float) $row->amount, 0, ',', '.') }}</span>
        @endscope

        @scope('cell_method_label', $row)
            @if ($row->method === 'transfer')
                <x-mary-badge value="{{ __('Transfer') }}" class="badge-info badge-soft" />
                @if ($row->bank_name)
                    <div class="text-xs text-base-content/60 mt-1">
                        {{ $row->bank_name }} • {{ $row->account_number }}
                    </div>
                @endif
            @else
                <x-mary-badge value="{{ __('Cash') }}" class="badge-success badge-soft" />
            @endif
        @endscope
    </x-mary-table>

    <x-mary-modal
        wire:model="formModal"
        title="{{ __('Pencairan Baru') }}"
        subtitle="{{ __('Hanya nasabah dengan saldo tersedia > 0 yang bisa dipilih.') }}"
        separator
        box-class="max-w-lg"
    >
        <x-mary-form wire:submit="save" no-separator>
            <x-mary-select
                wire:model.live="user_id"
                label="{{ __('Nasabah') }}"
                :options="$this->nasabahOptions"
                option-label="name"
                option-value="id"
                placeholder="{{ __('Pilih nasabah') }}"
                icon="o-user"
                required
            />

            @if ($this->selectedBalance)
                <div class="rounded-lg bg-base-200 p-3 text-sm">
                    {{ __('Saldo tersedia') }}:
                    <span class="font-semibold">
                        Rp {{ number_format((float) $this->selectedBalance->saldo_tersedia, 2, ',', '.') }}
                    </span>
                </div>
            @endif

            <x-mary-input
                wire:model="amount"
                label="{{ __('Jumlah (Rp)') }}"
                type="number"
                step="0.01"
                min="0"
                icon="o-banknotes"
                required
            />

            <x-mary-select
                wire:model.live="method"
                label="{{ __('Metode') }}"
                :options="[
                    ['id' => 'cash', 'name' => __('Cash (tunai)')],
                    ['id' => 'transfer', 'name' => __('Transfer bank')],
                ]"
                option-label="name"
                option-value="id"
                required
            />

            @if ($method === 'transfer')
                <x-mary-input wire:model="bank_name" label="{{ __('Nama bank') }}" required />
                <x-mary-input wire:model="account_number" label="{{ __('Nomor rekening') }}" required />
                <x-mary-input wire:model="account_name" label="{{ __('Atas nama') }}" required />
            @endif

            <x-mary-textarea wire:model="notes" label="{{ __('Catatan') }}" rows="2" />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button
                    type="submit"
                    label="{{ __('Simpan Pencairan') }}"
                    class="btn-primary"
                    spinner="save"
                    data-test="withdrawal-save-button"
                />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</section>
