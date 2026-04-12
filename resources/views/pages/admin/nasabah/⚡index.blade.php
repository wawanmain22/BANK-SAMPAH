<?php

use App\Concerns\NasabahValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Nasabah')] class extends Component {
    use NasabahValidationRules, Toast, WithPagination;

    public string $search = '';

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public bool $is_member = false;

    public ?string $member_joined_at = null;

    public ?int $deletingUserId = null;

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
            ['key' => 'name', 'label' => __('Nama'), 'class' => 'w-48'],
            ['key' => 'email', 'label' => __('Email'), 'class' => 'hidden md:table-cell'],
            ['key' => 'phone', 'label' => __('Telepon'), 'class' => 'hidden lg:table-cell'],
            ['key' => 'member_label', 'label' => __('Member'), 'sortable' => false],
            ['key' => 'member_joined_label', 'label' => __('Bergabung'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ];
    }

    #[Computed]
    public function nasabahList()
    {
        return User::query()
            ->nasabah()
            ->when($this->search !== '', fn ($q) => $q->where(function ($q): void {
                $like = '%'.$this->search.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            }))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function rules(): array
    {
        return $this->nasabahRules($this->editingUserId);
    }

    public function startCreating(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    public function startEditing(int $id): void
    {
        $user = User::nasabah()->findOrFail($id);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) ($user->phone ?? '');
        $this->address = (string) ($user->address ?? '');
        $this->is_member = (bool) $user->is_member;
        $this->member_joined_at = $user->member_joined_at?->format('Y-m-d');

        $this->formModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if (! $validated['is_member']) {
            $validated['member_joined_at'] = null;
        }

        if ($this->editingUserId) {
            $user = User::nasabah()->findOrFail($this->editingUserId);
            $user->update($validated);
            $this->success(__('Nasabah berhasil diperbarui.'));
        } else {
            User::create([
                ...$validated,
                'role' => UserRole::Nasabah,
                'password' => Str::random(32),
            ]);
            $this->success(__('Nasabah berhasil ditambahkan.'));
        }

        $this->formModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingUserId = $id;
        $this->deleteModal = true;
    }

    public function delete(): void
    {
        if (! $this->deletingUserId) {
            return;
        }

        $user = User::nasabah()->findOrFail($this->deletingUserId);
        $user->delete();
        $this->deletingUserId = null;
        $this->deleteModal = false;

        $this->success(__('Nasabah dihapus.'));
    }

    private function resetForm(): void
    {
        $this->reset(['editingUserId', 'name', 'email', 'phone', 'address', 'is_member', 'member_joined_at']);
        $this->resetErrorBag();
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Nasabah') }}"
        subtitle="{{ __('Kelola data nasabah yang menabung atau menyumbang sampah.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                placeholder="{{ __('Cari nama, email, atau telepon...') }}"
                clearable
            />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                wire:click="startCreating"
                label="{{ __('Tambah Nasabah') }}"
                data-test="nasabah-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->nasabahList"
        with-pagination
        striped
        per-page="perPage"
    >
        @scope('cell_member_label', $row)
            @if ($row->is_member)
                <x-mary-badge value="{{ __('Member') }}" class="badge-success badge-soft" />
            @else
                <x-mary-badge value="{{ __('Non-member') }}" class="badge-ghost" />
            @endif
        @endscope

        @scope('cell_member_joined_label', $row)
            {{ $row->member_joined_at?->format('d M Y') ?? '—' }}
        @endscope

        @scope('cell_phone', $row)
            {{ $row->phone ?? '—' }}
        @endscope

        @scope('actions', $row)
            <div class="flex items-center gap-1">
                <x-mary-button
                    icon="o-pencil-square"
                    wire:click="startEditing({{ $row->id }})"
                    class="btn-ghost btn-sm"
                    spinner
                    data-test="nasabah-edit-{{ $row->id }}"
                />
                <x-mary-button
                    icon="o-trash"
                    wire:click="confirmDelete({{ $row->id }})"
                    class="btn-ghost btn-sm text-error"
                    data-test="nasabah-delete-{{ $row->id }}"
                />
            </div>
        @endscope
    </x-mary-table>

    <x-mary-modal wire:model="formModal" title="{{ $editingUserId ? __('Edit Nasabah') : __('Tambah Nasabah') }}" subtitle="{{ __('Email dipakai sebagai identitas akun nasabah.') }}" separator box-class="max-w-xl">
        <x-mary-form wire:submit="save" no-separator>
            <div class="grid gap-4 md:grid-cols-2">
                <x-mary-input wire:model="name" label="{{ __('Nama') }}" icon="o-user" required />
                <x-mary-input wire:model="email" label="{{ __('Email') }}" icon="o-envelope" type="email" required />
                <x-mary-input wire:model="phone" label="{{ __('Telepon') }}" icon="o-phone" type="tel" />
                <x-mary-input wire:model="member_joined_at" label="{{ __('Tanggal bergabung member') }}" type="date" :disabled="! $is_member" />
            </div>

            <x-mary-textarea wire:model="address" label="{{ __('Alamat') }}" rows="3" />

            <x-mary-toggle wire:model.live="is_member" label="{{ __('Member (berhak mendapatkan poin)') }}" right />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button
                    label="{{ __('Simpan') }}"
                    class="btn-primary"
                    type="submit"
                    spinner="save"
                    data-test="nasabah-save-button"
                />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="deleteModal" title="{{ __('Hapus Nasabah') }}" box-class="max-w-md">
        <p class="text-sm text-base-content/70">
            {{ __('Data nasabah akan dihapus permanen. Transaksi terkait akan tetap tersimpan di sistem.') }}
        </p>

        <x-slot:actions>
            <x-mary-button label="{{ __('Batal') }}" @click="$wire.deleteModal = false" />
            <x-mary-button
                label="{{ __('Hapus') }}"
                class="btn-error"
                wire:click="delete"
                spinner
                data-test="nasabah-confirm-delete"
            />
        </x-slot:actions>
    </x-mary-modal>
</section>
