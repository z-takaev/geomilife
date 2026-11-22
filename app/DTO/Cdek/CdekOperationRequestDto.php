<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

use App\Enums\Cdek\CdekRequestStateEnum;
use DateTimeImmutable;

final readonly class CdekOperationRequestDto
{
    /**
     * @param  list<array{code: string, message: string}>  $errors
     * @param  list<array{code: string, message: string}>  $warnings
     */
    public function __construct(
        public ?string $uuid,
        public string $type,
        public DateTimeImmutable $dateTime,
        public CdekRequestStateEnum $state,
        public array $errors = [],
        public array $warnings = [],
    ) {}
}
