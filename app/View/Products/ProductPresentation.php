<?php

declare(strict_types=1);

namespace App\View\Products;

use App\Models\Product;
use App\Models\ProductVariant;

final readonly class ProductPresentation
{
    public array $selectedVariant;

    public array $variantOptions;

    public string $productUrl;

    public bool $hasDiscount;

    public function __construct(Product $product, ?int $variantId = null)
    {
        $options = $product->activeVariants->mapWithKeys(static fn (ProductVariant $variant): array => [
            $variant->getKey() => [
                'id' => $variant->getKey(),
                'label' => $variant->label(),
                'price' => $variant->currentPrice()->format(),
                'oldPrice' => $variant->oldPrice()?->format(),
                'isPreorder' => $variant->is_preorder,
            ],
        ]);

        $this->selectedVariant = $options->get($product->selectedVariant($variantId)->getKey());
        $this->variantOptions = $options->values()->all();
        $this->productUrl = route('products.show', [$product, 'variant' => $this->selectedVariant['id']]);
        $this->hasDiscount = $product->hasDiscount();
    }
}
