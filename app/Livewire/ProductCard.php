<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class ProductCard extends Component
{
    public Product $product;

    #[Locked]
    public ?int $initialVariantId = null;

    public function mount(Product $product, ?int $initialVariantId = null): void
    {
        $this->product = $product;
        $this->initialVariantId = $initialVariantId;
    }

    public function render(): View
    {
        $selectedVariant = $this->selectedVariant();

        return view('livewire.product-card', [
            'hasDiscount' => $this->hasDiscount(),
            'imageUrl' => $this->imageUrl(),
            'isPreorder' => $selectedVariant->is_preorder,
            'oldPrice' => $selectedVariant->oldPrice(),
            'price' => $selectedVariant->currentPrice(),
            'selectedVariantId' => $selectedVariant->getKey(),
            'variantOptions' => $this->product->variantOptions(),
        ]);
    }

    private function selectedVariant(): ProductVariant
    {
        if ($this->initialVariantId !== null) {
            $catalogVariant = $this->product->variants->firstWhere('id', $this->initialVariantId);

            if ($catalogVariant instanceof ProductVariant) {
                return $catalogVariant;
            }
        }

        return $this->product->variants->firstOrFail();
    }

    private function hasDiscount(): bool
    {
        return $this->product->variants->contains(
            static fn (ProductVariant $variant): bool => $variant->hasDiscount(),
        );
    }

    private function imageUrl(): string
    {
        return $this->product->getFirstMediaUrl('image', 'card');
    }
}
