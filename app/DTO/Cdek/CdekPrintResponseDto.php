<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

use App\Enums\Cdek\CdekPrintStateEnum;
use DateTimeImmutable;

final readonly class CdekPrintResponseDto
{
    public function __construct(
        public ?string $uuid,
        public ?CdekPrintStateEnum $status,
        public ?string $url,
        public ?DateTimeImmutable $statusDateTime,
        public CdekOperationResponseDto $operation,
    ) {}
}
