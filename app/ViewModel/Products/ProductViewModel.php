<?php

declare(strict_types=1);

namespace App\ViewModel\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use LogicException;

final readonly class ProductViewModel
{
    public ProductVariantViewModel $selectedVariant;

    /** @var list<ProductVariantViewModel> */
    public array $variantOptions;

    public string $productUrl;

    public bool $hasDiscount;

    public function __construct(Product $product, ?int $variantId = null)
    {
        if (! $product->relationLoaded('activeVariants')) {
            throw new LogicException('Связь activeVariants должна быть загружена до создания ProductViewModel.');
        }

        $this->selectedVariant = ProductVariantViewModel::fromModel($product->selectedVariant($variantId));
        $this->variantOptions = $product->activeVariants
            ->map(static fn (ProductVariant $variant): ProductVariantViewModel => ProductVariantViewModel::fromModel($variant))
            ->values()
            ->all();

        $this->productUrl = route('products.show', [$product, 'variant' => $this->selectedVariant->id]);
        $this->hasDiscount = $product->hasDiscount();
    }
}
