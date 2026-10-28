<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Repositories\Products\ProductRepository;
use App\ViewModel\Products\ProductViewModel;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class QuickView extends Component
{
    public ?int $productId = null;

    public ?int $variantId = null;

    public int $opening = 0;

    public function open(int $productId, ?int $variantId = null): void
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->opening++;
    }

    public function render(ProductRepository $products): View
    {
        $product = $this->productId === null ? null : $products->getDetails($this->productId);
        $images = $product?->images() ?? collect();
        $productViewModel = $product === null ? null : new ProductViewModel($product, $this->variantId);

        return view('livewire.quick-view', compact('product', 'images', 'productViewModel'));
    }

    protected function getListeners(): array
    {
        return ['open-quick-view' => 'open'];
    }
}
