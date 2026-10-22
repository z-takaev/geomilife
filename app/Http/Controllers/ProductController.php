<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Repositories\Products\ProductRepository;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use App\Support\Seo\Schemas\ProductSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function show(Product $product): View
    {
        abort_unless($product->is_active && $product->variants()->active()->exists(), 404);

        $product->load([
            'attributeValues.productAttribute',
            'category',
            'media',
            'variants' => static function (HasMany $query): void {
                $query->active();
            },
        ]);

        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Каталог'), route('catalog.index')),
            BreadcrumbItem::make($product->name, route('products.show', $product)),
        );

        seo()->forModel(
            seoable: $product,
            fallbackTitle: $product->name,
        )
            ->image($product->getFirstMediaUrl('image'))
            ->type('product')
            ->schema(ProductSchema::make($product))
            ->schema(BreadcrumbSchema::make($breadcrumbs));

        $recommendedProducts = $this->productRepository->getRecommendedProducts($product);

        return view('products.show', compact('product', 'recommendedProducts', 'breadcrumbs'));
    }
}
