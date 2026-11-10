<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

final readonly class CdekCityResponseDto
{
    public function __construct(
        public int $code,
        public string $name,
        public ?string $region = null,
        public ?string $subRegion = null,
    ) {}
}
