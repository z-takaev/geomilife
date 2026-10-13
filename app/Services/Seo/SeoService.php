<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Contracts\Seoable;
use App\DTO\Seo\SeoData;
use App\Enums\SeoRoute;
use App\Models\SeoMetadata;

final class SeoService
{
    public function forModel(Seoable $seoable, string $fallbackTitle, string $fallbackDescription): SeoData
    {
        return $this->resolve(
            metadata: $seoable->seoMetadata()->first(),
            fallbackTitle: $fallbackTitle,
            fallbackDescription: $fallbackDescription,
        );
    }

    public function forRoute(SeoRoute $route): SeoData
    {
        return $this->resolve(
            metadata: SeoMetadata::query()->where('route_name', $route->value)->first(),
            fallbackTitle: $route->title(),
            fallbackDescription: $route->description(),
        );
    }

    private function resolve(?SeoMetadata $metadata, string $fallbackTitle, string $fallbackDescription): SeoData
    {
        return new SeoData(
            title: filled($metadata?->title) ? trim($metadata->title) : $fallbackTitle,
            description: filled($metadata?->description) ? trim($metadata->description) : $fallbackDescription,
        );
    }
}
