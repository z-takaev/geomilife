<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

use InvalidArgumentException;

final readonly class CdekOrderPackageDto
{
    /**
     * @param  int  $weight  Вес упаковки в граммах.
     * @param  int  $length  Длина в сантиметрах.
     * @param  int  $width  Ширина в сантиметрах.
     * @param  int  $height  Высота в сантиметрах.
     * @param  list<CdekOrderItemDto>  $items  Товары в упаковке.
     */
    public function __construct(
        public string $number,
        public int $weight,
        public int $length,
        public int $width,
        public int $height,
        public array $items,
    ) {
        if (trim($number) === '' || min($weight, $length, $width, $height) < 1) {
            throw new InvalidArgumentException('Укажите номер, положительный вес и размеры упаковки.');
        }

        if (! array_is_list($items) || count($items) < 1 || count($items) > 126) {
            throw new InvalidArgumentException('Некорректный список items.');
        }

        foreach ($items as $item) {
            if (! $item instanceof CdekOrderItemDto) {
                throw new InvalidArgumentException('Поле items должно содержать объекты CdekOrderItemDto.');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'number' => $this->number,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'items' => array_map(static fn (CdekOrderItemDto $item): array => $item->toArray(), $this->items),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
