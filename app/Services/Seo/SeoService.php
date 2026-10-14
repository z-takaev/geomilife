<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Contracts\Seoable;
use App\DTO\Seo\SeoDto;
use App\Models\Seo;

final class SeoService
{
    public function forModel(Seoable $seoable, string $fallbackTitle): SeoDto
    {
        return $this->resolve(
            seo: $seoable->seo()->first(),
            fallbackTitle: $fallbackTitle,
        );
    }

    public function forRoute(string $routeName, string $fallbackTitle): SeoDto
    {
        return $this->resolve(
            seo: Seo::query()->where('route_name', $routeName)->first(),
            fallbackTitle: $fallbackTitle,
        );
    }

    private function resolve(?Seo $seo, string $fallbackTitle): SeoDto
    {
        return new SeoDto(
            title: filled($seo?->title) ? trim($seo->title) : $fallbackTitle,
            description: filled($seo?->description) ? trim($seo->description) : null,
        );
    }
}
