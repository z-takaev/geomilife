<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

final readonly class CdekWebhookResponseDto
{
    public function __construct(
        public string $uuid,
        public string $type,
        public string $url,
    ) {}
}
