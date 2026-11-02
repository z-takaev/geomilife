<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Responses;

use App\Enums\Cdek\CdekRequestStateEnum;
use App\Support\Traits\Makeable;

final readonly class CdekOperationResponseDto
{
    use Makeable;

    /**
     * @param  list<array{code: string, message: string}>  $errors
     * @param  list<array{code: string, message: string}>  $warnings
     * @param  list<array{uuid: ?string, type: string, dateTime: \DateTimeImmutable, state: CdekRequestStateEnum, errors: list<array{code: string, message: string}>, warnings: list<array{code: string, message: string}>}>  $requests
     */
    public function __construct(
        public ?string $uuid,
        public ?CdekRequestStateEnum $state,
        public array $errors = [],
        public array $warnings = [],
        public array $requests = [],
    ) {}
}
