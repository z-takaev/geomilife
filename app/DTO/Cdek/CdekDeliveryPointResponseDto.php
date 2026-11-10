<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

final readonly class CdekDeliveryPointResponseDto
{
    /**
     * @param  list<array{width: int, height: int, depth: int}>  $dimensions  Размеры ячеек постамата в сантиметрах.
     * @param  float|null  $weightMin  Минимальный вес в килограммах.
     * @param  float|null  $weightMax  Максимальный вес в килограммах.
     */
    public function __construct(
        public string $code,
        public string $type,
        public int $cityCode,
        public string $address,
        public float $latitude,
        public float $longitude,
        public string $workTime,
        public ?float $weightMin = null,
        public ?float $weightMax = null,
        public array $dimensions = [],
    ) {}
}
