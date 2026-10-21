<?php

declare(strict_types=1);

namespace App\Support\Seo\Schemas;

use App\Models\Article;
use App\Support\Seo\Schemas\Contracts\SchemaContract;

final readonly class ArticleSchema implements SchemaContract
{
    private function __construct(private Article $article) {}

    public static function make(Article $article): self
    {
        return new self($article);
    }

    public function toArray(): array
    {
        return array_filter([
            '@type' => 'Article',
            'headline' => $this->article->title,
            'description' => $this->article->excerpt,
            'image' => $this->article->getFirstMediaUrl('image') ?: null,
            'datePublished' => $this->article->published_at?->toAtomString(),
            'dateModified' => $this->article->updated_at?->toAtomString(),
            'mainEntityOfPage' => route('articles.show', $this->article),
            'publisher' => ['@id' => route('home').'#organization'],
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
