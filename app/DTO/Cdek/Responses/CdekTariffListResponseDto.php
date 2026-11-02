<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Responses;

use App\Support\Traits\Makeable;

final readonly class CdekTariffListResponseDto
{
    use Makeable;

    /**
     * @param  list<CdekTariffResponseDto>  $tariffs
     * @param  list<array{code: string, message: string}>  $warnings
     */
    public function __construct(
        public array $tariffs,
        public array $warnings = [],
    ) {}
}
