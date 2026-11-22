<?php

declare(strict_types=1);

namespace App\DTO\Orders;

use InvalidArgumentException;

final readonly class PackedPackageDto
{
    /**
     * @param  list<PackedItemDto>  $items
     */
    public function __construct(
        public ?int $boxId,
        public ?string $boxName,
        public string $number,
        public int $length,
        public int $width,
        public int $height,
        public int $innerLength,
        public int $innerWidth,
        public int $innerHeight,
        public int $packagingWeight,
        public int $weight,
        public array $items,
    ) {
        if (! array_is_list($items) || array_any($items, static fn (mixed $item): bool => ! $item instanceof PackedItemDto)) {
            throw new InvalidArgumentException('Поле items должно содержать объекты PackedItemDto.');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            boxId: isset($data['box_id']) ? (int) $data['box_id'] : null,
            boxName: isset($data['box_name']) ? (string) $data['box_name'] : null,
            number: (string) $data['number'],
            length: (int) $data['length'],
            width: (int) $data['width'],
            height: (int) $data['height'],
            innerLength: (int) $data['inner_length'],
            innerWidth: (int) $data['inner_width'],
            innerHeight: (int) $data['inner_height'],
            packagingWeight: (int) ($data['packaging_weight'] ?? 0),
            weight: (int) $data['weight'],
            items: array_map(PackedItemDto::fromArray(...), $data['items']),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'box_id' => $this->boxId,
            'box_name' => $this->boxName,
            'number' => $this->number,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'inner_length' => $this->innerLength,
            'inner_width' => $this->innerWidth,
            'inner_height' => $this->innerHeight,
            'packaging_weight' => $this->packagingWeight,
            'weight' => $this->weight,
            'items' => array_map(static fn (PackedItemDto $item): array => $item->toArray(), $this->items),
        ];
    }
}
