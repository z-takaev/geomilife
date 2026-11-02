<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Responses;

use App\Support\Traits\Makeable;

final readonly class CdekCityResponseDto
{
    use Makeable;

    public function __construct(
        public int $code,
        public string $name,
        public ?string $region = null,
        public ?string $subRegion = null,
    ) {}
}
