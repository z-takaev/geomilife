<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Responses;

use App\Support\Traits\Makeable;
use DateTimeImmutable;

final readonly class CdekOrderResponseDto
{
    use Makeable;

    public function __construct(
        public ?string $uuid,
        public ?string $cdekNumber,
        public ?string $statusCode,
        public ?string $statusName,
        public ?DateTimeImmutable $statusDateTime,
        public CdekOperationResponseDto $operation,
    ) {}
}
