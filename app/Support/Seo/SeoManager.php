<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Contracts\Seoable;
use App\Models\Seo;
use App\Support\Seo\Schemas\Contracts\SchemaContract;
use Illuminate\Support\Str;

final class SeoManager
{
    /**
     * @var list<SchemaContract>
     */
    private array $schemas = [];

    private string $title;

    private ?string $description;

    private ?string $image;

    private string $type = 'website';

    public function __construct()
    {
        $this->reset();
    }

    public function forModel(Seoable $seoable, string $fallbackTitle): self
    {
        return $this->resolve(
            seo: $seoable->seo()->first(),
            fallbackTitle: $fallbackTitle,
        );
    }

    public function forRoute(string $routeName, string $fallbackTitle): self
    {
        return $this->resolve(
            seo: Seo::cachedRoutes()->get($routeName),
            fallbackTitle: $fallbackTitle,
        );
    }

    public function title(string $title): self
    {
        $title = trim($title);

        if ($title !== '') {
            $this->title = $title;
        }

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $this->filledValue($description);

        return $this;
    }

    public function image(?string $image): self
    {
        $this->image = $this->normalizeUrl($image)
            ?? $this->normalizeUrl(config('seo.image'));

        return $this;
    }

    public function type(string $type): self
    {
        $type = trim($type);

        if ($type !== '') {
            $this->type = $type;
        }

        return $this;
    }

    public function schema(SchemaContract $schema): self
    {
        $this->schemas[] = $schema;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCanonicalUrl(): string
    {
        return request()->url();
    }

    public function getLocale(): string
    {
        return match (app()->getLocale()) {
            'ru' => 'ru_RU',
            'en' => 'en_US',
            default => str_replace('-', '_', app()->getLocale()),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSchemas(): array
    {
        return array_map(
            static fn (SchemaContract $schema): array => $schema->toArray(),
            $this->schemas,
        );
    }

    private function resolve(?Seo $seo, string $fallbackTitle): self
    {
        $this->reset();

        $this->title = $this->filledValue($seo?->title)
            ?? $this->filledValue($fallbackTitle)
            ?? $this->title;
        $this->description = $this->filledValue($seo?->description)
            ?? $this->description;

        return $this;
    }

    private function reset(): void
    {
        $this->title = $this->filledValue(config('seo.title')) ?? 'GeoMiLife';
        $this->description = $this->filledValue(config('seo.description'));
        $this->image = $this->normalizeUrl(config('seo.image'));
        $this->type = 'website';
        $this->schemas = [];
    }

    private function filledValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function normalizeUrl(mixed $url): ?string
    {
        $url = $this->filledValue($url);

        if ($url === null) {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return asset(ltrim($url, '/'));
    }
}
