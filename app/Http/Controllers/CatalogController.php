<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        $categories = $this->categoryRepository->getActiveCatalog();
        $products = $this->productRepository->getCatalogProducts();
        seo()->forRoute(
            routeName: 'catalog.index',
            fallbackTitle: __('Каталог'),
        )->schema(BreadcrumbSchema::make([
            __('Главная') => route('home'),
            __('Каталог') => route('catalog.index'),
        ]));

        return view('catalog.index', compact('categories', 'products'));
    }
}
