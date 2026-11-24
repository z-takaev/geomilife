<?php

declare(strict_types=1);

namespace App\ViewModel\Products;

use App\Models\ProductVariant;
use JsonSerializable;

final readonly class ProductVariantViewModel implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $label,
        public string $price,
        public ?string $oldPrice,
        public bool $isPreorder,
    ) {}

    public static function fromModel(ProductVariant $variant): self
    {
        return new self(
            id: $variant->getKey(),
            label: $variant->label(),
            price: $variant->price->format(),
            oldPrice: $variant->old_price?->format(),
            isPreorder: $variant->is_preorder,
        );
    }

    /**
     * @return array{id: int, label: string, price: string, oldPrice: ?string, isPreorder: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'price' => $this->price,
            'oldPrice' => $this->oldPrice,
            'isPreorder' => $this->isPreorder,
        ];
    }
}
