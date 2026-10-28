<?php

declare(strict_types=1);

namespace App\Support\Seo\Schemas;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Seo\Schemas\Contracts\SchemaContract;
use App\Support\Values\Money;

final readonly class ProductSchema implements SchemaContract
{
    private function __construct(private Product $product, private ?int $variantId) {}

    public static function make(Product $product, ?int $variantId = null): self
    {
        return new self($product, $variantId);
    }

    public function toArray(): array
    {
        $variant = $this->product->selectedVariant($this->variantId);

        return array_filter([
            '@type' => 'Product',
            'name' => $this->product->name,
            'description' => $this->product->description,
            'image' => $this->product->getFirstMediaUrl('image') ?: null,
            'sku' => $variant->sku,
            'brand' => [
                '@type' => 'Brand',
                'name' => 'GeoMiLife',
            ],
            'offers' => $this->offer($variant),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array<string, string>
     */
    private function offer(ProductVariant $variant): array
    {
        return [
            '@type' => 'Offer',
            'url' => route('products.show', [$this->product, 'variant' => $variant->getKey()]),
            'priceCurrency' => Money::CURRENCY,
            'price' => $variant->currentPrice()->toRubles(),
            'availability' => $variant->is_preorder
                ? 'https://schema.org/PreOrder'
                : 'https://schema.org/InStock',
        ];
    }
}
