<?php

declare(strict_types=1);

namespace App\View\Components\Products;

use App\Models\Product;
use App\ViewModel\Products\ProductViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Card extends Component
{
    public ProductViewModel $productViewModel;

    public string $imageUrl;

    public function __construct(public Product $product)
    {
        $this->productViewModel = new ProductViewModel($product, $product->catalog_variant_id);
        $this->imageUrl = $product->getFirstMediaUrl('image', 'card');
    }

    public function render(): View
    {
        return view('components.products.card');
    }
}
