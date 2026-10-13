<?php

declare(strict_types=1);

namespace App\DTO\Seo;

final readonly class SeoData
{
    public function __construct(
        public string $title,
        public string $description,
    ) {}
}
