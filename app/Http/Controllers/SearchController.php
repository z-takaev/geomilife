<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Repositories\Categories\CategoryRepository;
use App\Repositories\Products\ProductRepository;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class SearchController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function __invoke(SearchRequest $request): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Поиск'), route('search')),
        );

        seo()->forRoute(
            routeName: 'search',
            fallbackTitle: __('Поиск'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $query = $request->searchQuery();

        $categories = $this->categoryRepository->search($query);
        $products = $this->productRepository->search($query);

        return view('search.index', compact('query', 'categories', 'products', 'breadcrumbs'));
    }
}
