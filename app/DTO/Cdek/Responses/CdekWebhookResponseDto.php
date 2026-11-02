<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Responses;

use App\Support\Traits\Makeable;

final readonly class CdekWebhookResponseDto
{
    use Makeable;

    public function __construct(
        public string $uuid,
        public string $type,
        public string $url,
    ) {}
}
