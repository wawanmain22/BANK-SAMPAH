<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('Bank Sampah');
    }

    public function test_edukasi_index_shows_published_articles_only(): void
    {
        $published = Article::factory()->create(['title' => 'Artikel Terbit', 'published_at' => now()->subDay()]);
        $draft = Article::factory()->draft()->create(['title' => 'Artikel Draft']);

        $this->get(route('public.edukasi.index'))
            ->assertOk()
            ->assertSee('Artikel Terbit')
            ->assertDontSee('Artikel Draft');
    }

    public function test_edukasi_show_renders_published_article(): void
    {
        $article = Article::factory()->create([
            'title' => 'Manfaat Daur Ulang',
            'content' => 'Isi artikel lengkap.',
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('public.edukasi.show', $article))
            ->assertOk()
            ->assertSee('Manfaat Daur Ulang')
            ->assertSee('Isi artikel lengkap');
    }

    public function test_edukasi_show_404_for_draft(): void
    {
        $article = Article::factory()->draft()->create();

        $this->get(route('public.edukasi.show', $article))
            ->assertNotFound();
    }

    public function test_merchandise_index_shows_active_products(): void
    {
        Product::factory()->create(['name' => 'Paving Block', 'is_active' => true]);
        Product::factory()->inactive()->create(['name' => 'Produk Nonaktif']);

        $this->get(route('public.merchandise.index'))
            ->assertOk()
            ->assertSee('Paving Block')
            ->assertDontSee('Produk Nonaktif');
    }
}
