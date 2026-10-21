<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Repositories\Categories\CategoryRepository;
use App\Repositories\Products\ProductRepository;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class CatalogController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function __invoke(): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Каталог'), route('catalog.index')),
        );

        seo()->forRoute(
            routeName: 'catalog.index',
            fallbackTitle: __('Каталог'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $categories = $this->categoryRepository->getActiveCatalog();
        $products = $this->productRepository->getCatalogProducts();

        return view('catalog.index', compact('categories', 'products', 'breadcrumbs'));
    }
}
