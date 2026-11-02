<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Responses;

use App\Enums\Cdek\CdekPrintStateEnum;
use App\Support\Traits\Makeable;
use DateTimeImmutable;

final readonly class CdekPrintResponseDto
{
    use Makeable;

    public function __construct(
        public ?string $uuid,
        public ?CdekPrintStateEnum $status,
        public ?string $url,
        public ?DateTimeImmutable $statusDateTime,
        public CdekOperationResponseDto $operation,
    ) {}
}
