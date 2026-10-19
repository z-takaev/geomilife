<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Repositories\Products\ProductRepository;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductController extends Controller
{
    public function __construct(
        private readonly SeoService $seoService,
        private readonly ProductRepository $productRepository,
    ) {}

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'attributeValues.productAttribute',
            'category',
            'media',
            'variants' => static function (HasMany $query): void {
                $query->active();
            },
        ]);
        $recommendedProducts = $this->productRepository->getRecommendedProducts($product);

        $seo = $this->seoService->forModel(
            seoable: $product,
            fallbackTitle: $product->name,
        );

        return view('products.show', compact('product', 'recommendedProducts', 'seo'));
    }
}
