<?php

declare(strict_types=1);

namespace App\DTO\Orders;

use App\Support\Values\Money;

final readonly class CheckoutItemDto
{
    public function __construct(
        public int $productId,
        public int $productVariantId,
        public string $productName,
        public string $variantName,
        public string $sku,
        public int $quantity,
        public Money $price,
        public ?Money $oldPrice,
        public Money $total,
        public int $weight,
        public int $length,
        public int $width,
        public int $height,
        public string $image,
        public string $url,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            productVariantId: (int) $data['product_variant_id'],
            productName: (string) $data['product_name'],
            variantName: (string) $data['variant_name'],
            sku: (string) $data['sku'],
            quantity: (int) $data['quantity'],
            price: Money::fromMinor((int) $data['price_minor']),
            oldPrice: $data['old_price_minor'] === null ? null : Money::fromMinor((int) $data['old_price_minor']),
            total: Money::fromMinor((int) $data['total_minor']),
            weight: (int) $data['weight'],
            length: (int) $data['length'],
            width: (int) $data['width'],
            height: (int) $data['height'],
            image: (string) $data['image'],
            url: (string) $data['url'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_variant_id' => $this->productVariantId,
            'product_name' => $this->productName,
            'variant_name' => $this->variantName,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'price_minor' => $this->price->minorAmount(),
            'old_price_minor' => $this->oldPrice?->minorAmount(),
            'total_minor' => $this->total->minorAmount(),
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'image' => $this->image,
            'url' => $this->url,
        ];
    }

    /** @return array<string, int|null> */
    public function fingerprintData(): array
    {
        return [
            'product_id' => $this->productId,
            'product_variant_id' => $this->productVariantId,
            'quantity' => $this->quantity,
            'price_minor' => $this->price->minorAmount(),
            'old_price_minor' => $this->oldPrice?->minorAmount(),
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
