<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
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
        $category = null;
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Каталог'), route('catalog.index')),
        );

        seo()->forRoute(
            routeName: 'catalog.index',
            fallbackTitle: __('Каталог'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $categories = $this->categoryRepository->getActiveCatalog();
        $products = $this->productRepository->getCatalogProducts();

        return view('catalog.index', compact('category', 'categories', 'products', 'breadcrumbs'));
    }

    public function show(Category $category): View
    {
        abort_unless(
            $category->is_active
                && $category->ancestors()->where('is_active', false)->doesntExist(),
            404,
        );

        $category->loadMissing(['media', 'parent']);

        $breadcrumbItems = [
            BreadcrumbItem::make(__('Каталог'), route('catalog.index')),
        ];

        if ($category->parent !== null) {
            $breadcrumbItems[] = BreadcrumbItem::make(
                $category->parent->name,
                route('catalog.category', $category->parent),
            );
        }

        $breadcrumbItems[] = BreadcrumbItem::make(
            $category->name,
            route('catalog.category', $category),
        );

        $breadcrumbs = Breadcrumbs::make(...$breadcrumbItems);

        seo()->forModel(
            seoable: $category,
            fallbackTitle: $category->name,
        )
            ->image($category->getFirstMediaUrl('image'))
            ->schema(BreadcrumbSchema::make($breadcrumbs));

        $categories = $this->categoryRepository->getActiveCatalog();
        $products = $this->productRepository->getCatalogProducts($category);

        return view('catalog.index', compact('category', 'categories', 'products', 'breadcrumbs'));
    }
}
