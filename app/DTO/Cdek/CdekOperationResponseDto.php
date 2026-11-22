<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

use App\Enums\Cdek\CdekRequestStateEnum;

final readonly class CdekOperationResponseDto
{
    /**
     * @param  list<array{code: string, message: string}>  $errors
     * @param  list<array{code: string, message: string}>  $warnings
     * @param  list<CdekOperationRequestDto>  $requests
     */
    public function __construct(
        public ?string $uuid,
        public ?CdekRequestStateEnum $state,
        public array $errors = [],
        public array $warnings = [],
        public array $requests = [],
    ) {}
}
