<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\Categories\CategoryRepository;
use App\Repositories\Products\ProductRepository;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    public function __construct(
        private readonly SeoService $seoService,
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $categories = $this->categoryRepository->getActiveCatalog();
        $products = $this->productRepository->getCatalogProducts();
        $seo = $this->seoService->forRoute(
            routeName: 'search.index',
            fallbackTitle: __('Поиск'),
        );

        return view('search.index', compact('query', 'categories', 'products', 'seo'));
    }
}
