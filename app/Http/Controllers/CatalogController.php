<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\Categories\CategoryRepository;
use App\Repositories\Products\ProductRepository;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class CatalogController extends Controller
{
    public function __construct(
        private readonly SeoService $seoService,
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function __invoke(): View
    {
        $categories = $this->categoryRepository->getActiveCatalog();
        $products = $this->productRepository->getCatalogProducts();
        $seo = $this->seoService->forRoute(
            routeName: 'catalog.index',
            fallbackTitle: __('Каталог'),
        );

        return view('catalog.index', compact('categories', 'products', 'seo'));
    }
}
