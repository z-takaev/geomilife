<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Repositories\Categories\CategoryRepository;
use App\Repositories\Products\ProductRepository;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function __invoke(Request $request): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Поиск'), route('search')),
        );

        seo()->forRoute(
            routeName: 'search',
            fallbackTitle: __('Поиск'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $queryValue = $request->query('q', '');
        $query = is_string($queryValue) ? mb_substr(trim($queryValue), 0, 255) : '';
        $categories = $this->categoryRepository->search($query);
        $products = $this->productRepository->search($query);

        return view('search.index', compact('query', 'categories', 'products', 'breadcrumbs'));
    }
}
