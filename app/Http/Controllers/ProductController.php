<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class ProductController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $seo = $this->seoService->forModel(
            seoable: $product,
            fallbackTitle: $product->name,
        );

        return view('products.show', compact('product', 'seo'));
    }
}
