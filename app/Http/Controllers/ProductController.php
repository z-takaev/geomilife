<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Repositories\Products\ProductRepository;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use App\Support\Seo\Schemas\ProductSchema;
use App\View\Products\ProductPresentation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function show(string $product, Request $request): View
    {
        $product = $this->productRepository->getDetails($product);
        abort_if($product === null, 404);
        $presentation = new ProductPresentation($product, $request->integer('variant'));

        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Каталог'), route('catalog.index')),
            BreadcrumbItem::make($product->category->name, route('catalog.category', $product->category)),
            BreadcrumbItem::make($product->name, route('products.show', $product)),
        );

        seo()->forModel(
            seoable: $product,
            fallbackTitle: $product->name,
        )
            ->image($product->getFirstMediaUrl('image'))
            ->type('product')
            ->schema(ProductSchema::make($product, $presentation->selectedVariant['id']))
            ->schema(BreadcrumbSchema::make($breadcrumbs));

        $recommendedProducts = $this->productRepository->getRecommendedProducts($product);
        $images = $product->images();

        return view(
            'products.show',
            compact('product', 'recommendedProducts', 'breadcrumbs', 'presentation', 'images'),
        );
    }
}
