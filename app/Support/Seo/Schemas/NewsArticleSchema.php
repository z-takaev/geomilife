<?php

declare(strict_types=1);

namespace App\Support\Seo\Schemas;

use App\Models\News;
use App\Support\Seo\Schemas\Contracts\SchemaContract;

final readonly class NewsArticleSchema implements SchemaContract
{
    private function __construct(private News $news) {}

    public static function make(News $news): self
    {
        return new self($news);
    }

    public function toArray(): array
    {
        return array_filter([
            '@type' => 'NewsArticle',
            'headline' => $this->news->title,
            'description' => $this->news->excerpt,
            'image' => $this->news->getFirstMediaUrl('image') ?: null,
            'datePublished' => $this->news->published_at?->toAtomString(),
            'dateModified' => $this->news->updated_at?->toAtomString(),
            'mainEntityOfPage' => route('news.show', $this->news),
            'publisher' => ['@id' => route('home').'#organization'],
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
