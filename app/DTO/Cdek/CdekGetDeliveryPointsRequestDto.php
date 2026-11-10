<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

use InvalidArgumentException;

final readonly class CdekGetDeliveryPointsRequestDto
{
    /**
     * @param  int  $cityCode  Код населённого пункта СДЭК.
     * @param  int|null  $weightMax  Вес посылки в килограммах для проверки ограничений офиса.
     * @param  int|null  $length  Длина посылки в сантиметрах.
     * @param  int|null  $width  Ширина посылки в сантиметрах.
     * @param  int|null  $height  Высота посылки в сантиметрах.
     */
    public function __construct(
        public int $cityCode,
        public ?int $weightMax = null,
        public ?int $length = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?int $size = null,
        public ?int $page = null,
    ) {
        if ($cityCode < 1) {
            throw new InvalidArgumentException('Код города должен быть больше нуля.');
        }

        foreach ([$weightMax, $length, $width, $height, $size, $page] as $value) {
            if ($value !== null && $value < 0) {
                throw new InvalidArgumentException('Параметры фильтра не могут быть отрицательными.');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'city_code' => $this->cityCode,
            'type' => 'PVZ',
            'weight_max' => $this->weightMax,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'size' => $this->size,
            'page' => $this->page,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
