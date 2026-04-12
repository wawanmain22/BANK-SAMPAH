<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(4),
            'excerpt' => fake()->sentence(15),
            'content' => fake()->paragraphs(4, true),
            'featured_image' => null,
            'published_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'author_id' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }
}
