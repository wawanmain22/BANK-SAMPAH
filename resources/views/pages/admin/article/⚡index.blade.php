<?php

use App\Concerns\ArticleValidationRules;
use App\Models\Article;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new #[Title('Edukasi')] class extends Component {
    use ArticleValidationRules, Toast, WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $title = '';

    public string $excerpt = '';

    public string $content = '';

    public string $featured_image = '';

    public string $images_text = '';

    public ?string $published_at = null;

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
            ['key' => 'title', 'label' => __('Judul'), 'sortable' => false],
            ['key' => 'published_at_label', 'label' => __('Terbit'), 'class' => 'hidden md:table-cell', 'sortable' => false],
            ['key' => 'author_name', 'label' => __('Penulis'), 'class' => 'hidden lg:table-cell', 'sortable' => false],
            ['key' => 'status_label', 'label' => __('Status'), 'sortable' => false],
        ];
    }

    #[Computed]
    public function articles()
    {
        return Article::query()
            ->with('author:id,name')
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function rules(): array
    {
        return $this->articleRules($this->editingId);
    }

    public function startCreating(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    public function startEditing(int $id): void
    {
        $article = Article::findOrFail($id);

        $this->editingId = $article->id;
        $this->title = $article->title;
        $this->excerpt = (string) ($article->excerpt ?? '');
        $this->content = $article->content;
        $this->featured_image = (string) ($article->featured_image ?? '');
        $this->images_text = is_array($article->images) ? implode("\n", $article->images) : '';
        $this->published_at = $article->published_at?->format('Y-m-d\TH:i');

        $this->formModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $gallery = collect(preg_split('/\r\n|\r|\n/', $this->images_text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $validated['images'] = $gallery ?: null;
        unset($validated['images_text']);

        if ($this->editingId) {
            Article::findOrFail($this->editingId)->update($validated);
            $this->success(__('Artikel berhasil diperbarui.'));
        } else {
            Article::create([
                ...$validated,
                'slug' => Str::slug($validated['title']).'-'.Str::random(4),
                'author_id' => Auth::id(),
            ]);
            $this->success(__('Artikel berhasil dipublikasi.'));
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

        Article::findOrFail($this->deletingId)->delete();
        $this->deletingId = null;
        $this->deleteModal = false;

        $this->success(__('Artikel dihapus.'));
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'excerpt', 'content', 'featured_image', 'images_text', 'published_at']);
        $this->resetErrorBag();
    }
}; ?>

<section class="w-full">
    <x-mary-header
        title="{{ __('Edukasi') }}"
        subtitle="{{ __('Artikel edukasi tentang sampah, daur ulang, dan dampak lingkungan.') }}"
        separator
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                placeholder="{{ __('Cari judul...') }}"
                clearable
            />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button
                icon="o-plus"
                class="btn-primary"
                label="{{ __('Tulis Artikel') }}"
                wire:click="startCreating"
                data-test="article-create-button"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-table
        :headers="$this->headers"
        :rows="$this->articles"
        with-pagination
        striped
    >
        @scope('cell_title', $row)
            <div class="font-medium">{{ $row->title }}</div>
            @if ($row->excerpt)
                <div class="text-xs text-base-content/60 line-clamp-1">{{ $row->excerpt }}</div>
            @endif
        @endscope

        @scope('cell_published_at_label', $row)
            {{ $row->published_at?->format('d M Y H:i') ?? '—' }}
        @endscope

        @scope('cell_author_name', $row)
            {{ $row->author?->name ?? '—' }}
        @endscope

        @scope('cell_status_label', $row)
            @if ($row->isPublished())
                <x-mary-badge value="{{ __('Terbit') }}" class="badge-success badge-soft" />
            @elseif ($row->published_at)
                <x-mary-badge value="{{ __('Terjadwal') }}" class="badge-warning badge-soft" />
            @else
                <x-mary-badge value="{{ __('Draft') }}" class="badge-ghost" />
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
        title="{{ $editingId ? __('Edit Artikel') : __('Tulis Artikel') }}"
        separator
        box-class="max-w-3xl"
    >
        <x-mary-form wire:submit="save" no-separator>
            <x-mary-input wire:model="title" label="{{ __('Judul') }}" required />
            <x-mary-textarea wire:model="excerpt" label="{{ __('Ringkasan') }}" rows="2" />
            <x-mary-textarea wire:model="content" label="{{ __('Isi artikel') }}" rows="10" required />
            <x-mary-input wire:model="featured_image" label="{{ __('URL gambar utama') }}" icon="o-photo" placeholder="https://... atau /images/demo/edukasi.jpg" hint="{{ __('Tampil sebagai thumbnail di daftar dan hero di detail.') }}" />
            <x-mary-textarea
                wire:model="images_text"
                label="{{ __('Galeri (opsional) — satu URL per baris') }}"
                rows="4"
                placeholder="/images/demo/edukasi.jpg&#10;https://..."
                hint="{{ __('Akan tampil sebagai grid di halaman detail artikel.') }}"
            />
            <x-mary-input wire:model="published_at" label="{{ __('Waktu terbit (kosongkan untuk draft)') }}" type="datetime-local" />

            <x-slot:actions>
                <x-mary-button label="{{ __('Batal') }}" @click="$wire.formModal = false" />
                <x-mary-button type="submit" label="{{ __('Simpan') }}" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="deleteModal" title="{{ __('Hapus Artikel') }}" box-class="max-w-md">
        <p class="text-sm text-base-content/70">
            {{ __('Artikel akan dihapus permanen.') }}
        </p>
        <x-slot:actions>
            <x-mary-button label="{{ __('Batal') }}" @click="$wire.deleteModal = false" />
            <x-mary-button label="{{ __('Hapus') }}" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</section>
