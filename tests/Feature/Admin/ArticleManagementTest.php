<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_gated_by_role(): void
    {
        $this->get(route('admin.article.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.article.index'))
            ->assertForbidden();
    }

    public function test_admin_can_publish_article(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test('pages::admin.article.index')
            ->call('startCreating')
            ->set('title', 'Manfaat Memilah Sampah')
            ->set('excerpt', 'Belajar memilah sampah dari rumah.')
            ->set('content', 'Konten lengkap artikel...')
            ->set('published_at', now()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('articles', [
            'title' => 'Manfaat Memilah Sampah',
            'author_id' => $admin->id,
        ]);
    }

    public function test_article_can_be_saved_as_draft(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test('pages::admin.article.index')
            ->call('startCreating')
            ->set('title', 'Draft')
            ->set('content', 'Konten')
            ->set('published_at', null)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('articles', [
            'title' => 'Draft',
            'published_at' => null,
        ]);
    }
}
