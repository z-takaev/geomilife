<?php

declare(strict_types=1);

namespace App\DTO\Orders;

final readonly class PackedItemDto
{
    public function __construct(
        public ?CheckoutItemDto $checkoutItem,
        public int $productVariantId,
        public int $quantity,
        public int $weight,
        public int $length,
        public int $width,
        public int $height,
        public int $x,
        public int $y,
        public int $z,
        public int $packedWidth,
        public int $packedLength,
        public int $packedHeight,
    ) {}

    public static function fromCheckoutItem(
        CheckoutItemDto $item,
        int $x,
        int $y,
        int $z,
        int $packedWidth,
        int $packedLength,
        int $packedHeight,
    ): self {
        return new self(
            checkoutItem: $item,
            productVariantId: $item->productVariantId,
            quantity: 1,
            weight: $item->weight,
            length: $item->length,
            width: $item->width,
            height: $item->height,
            x: $x,
            y: $y,
            z: $z,
            packedWidth: $packedWidth,
            packedLength: $packedLength,
            packedHeight: $packedHeight,
        );
    }

    public static function fromArray(array $data): self
    {
        $hasCheckoutSnapshot = array_all([
            'product_id', 'product_name', 'variant_name', 'sku',
            'price_minor', 'old_price_minor', 'total_minor', 'image', 'url',
        ], static fn (string $key): bool => array_key_exists($key, $data));

        return new self(
            checkoutItem: $hasCheckoutSnapshot ? CheckoutItemDto::fromArray($data) : null,
            productVariantId: (int) $data['product_variant_id'],
            quantity: (int) $data['quantity'],
            weight: (int) $data['weight'],
            length: (int) $data['length'],
            width: (int) $data['width'],
            height: (int) $data['height'],
            x: (int) ($data['x'] ?? 0),
            y: (int) ($data['y'] ?? 0),
            z: (int) ($data['z'] ?? 0),
            packedWidth: (int) ($data['packed_width'] ?? $data['width']),
            packedLength: (int) ($data['packed_length'] ?? $data['length']),
            packedHeight: (int) ($data['packed_height'] ?? $data['height']),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_replace($this->checkoutItem?->toArray() ?? [
            'product_variant_id' => $this->productVariantId,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ], [
            'quantity' => $this->quantity,
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z,
            'packed_width' => $this->packedWidth,
            'packed_length' => $this->packedLength,
            'packed_height' => $this->packedHeight,
        ]);
    }
}
