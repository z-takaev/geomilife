<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

use DateTimeImmutable;

final readonly class CdekOrderResponseDto
{
    public function __construct(
        public ?string $uuid,
        public ?string $cdekNumber,
        public ?string $statusCode,
        public ?string $statusName,
        public ?DateTimeImmutable $statusDateTime,
        public CdekOperationResponseDto $operation,
    ) {}
}
