<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Responses;

use App\Support\Traits\Makeable;

final readonly class CdekTariffResponseDto
{
    use Makeable;

    /**
     * @param  float  $deliverySum  Базовая стоимость; не является итогом с дополнительными услугами.
     * @param  int  $periodMin  Минимальный срок в рабочих днях.
     * @param  int  $periodMax  Максимальный срок в рабочих днях.
     */
    public function __construct(
        public int $code,
        public string $name,
        public int $deliveryMode,
        public float $deliverySum,
        public int $periodMin,
        public int $periodMax,
    ) {}
}
