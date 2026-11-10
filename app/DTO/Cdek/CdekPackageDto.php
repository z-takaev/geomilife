<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

use InvalidArgumentException;

final readonly class CdekPackageDto
{
    /**
     * @param  int  $weight  Вес в граммах.
     * @param  int|null  $length  Длина в сантиметрах.
     * @param  int|null  $width  Ширина в сантиметрах.
     * @param  int|null  $height  Высота в сантиметрах.
     */
    public function __construct(
        public int $weight,
        public ?int $length = null,
        public ?int $width = null,
        public ?int $height = null,
    ) {
        if ($weight < 1) {
            throw new InvalidArgumentException('Вес должен быть больше нуля.');
        }

        foreach ([$length, $width, $height] as $dimension) {
            if ($dimension !== null && $dimension < 1) {
                throw new InvalidArgumentException('Размеры должны быть больше нуля.');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
