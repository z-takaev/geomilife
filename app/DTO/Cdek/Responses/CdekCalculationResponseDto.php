<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Responses;

use App\Support\Traits\Makeable;

final readonly class CdekCalculationResponseDto
{
    use Makeable;

    /**
     * @param  int  $periodMin  Минимальный срок в рабочих днях.
     * @param  int  $periodMax  Максимальный срок в рабочих днях.
     * @param  int  $weightCalc  Расчётный вес в граммах.
     * @param  list<array{code: string, message: string}>  $warnings
     */
    public function __construct(
        public float $deliverySum,
        public float $totalSum,
        public string $currency,
        public int $periodMin,
        public int $periodMax,
        public int $weightCalc,
        public array $warnings = [],
    ) {}
}
