<?php

declare(strict_types=1);

namespace App\View\Components\Products;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class FavoriteButton extends Component
{
    public function __construct(public Product $product, public bool $compact = false) {}

    public function render(): View
    {
        return view('components.products.favorite-button');
    }
}
