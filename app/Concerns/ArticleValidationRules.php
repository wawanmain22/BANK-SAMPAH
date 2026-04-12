<?php

namespace App\Concerns;

use App\Models\Article;
use Illuminate\Validation\Rule;

trait ArticleValidationRules
{
    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function articleRules(?int $articleId = null): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:200',
                $articleId === null
                    ? Rule::unique(Article::class, 'title')
                    : Rule::unique(Article::class, 'title')->ignore($articleId),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
