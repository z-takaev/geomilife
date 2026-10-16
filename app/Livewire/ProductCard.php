<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ProductCard extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    public function render(): View
    {
        $selectedVariant = $this->selectedVariant();

        return view('livewire.product-card', [
            'hasDiscount' => $this->hasDiscount(),
            'imageUrl' => $this->imageUrl(),
            'oldPrice' => $selectedVariant->oldPrice(),
            'price' => $selectedVariant->currentPrice(),
        ]);
    }

    private function selectedVariant(): ProductVariant
    {
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
