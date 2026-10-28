<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class CatalogController extends Controller
{
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

        return view('catalog.index', compact('category', 'breadcrumbs'));
    }

    public function show(Category $category): View
    {
        abort_unless(Category::query()->visible()->whereKey($category->getKey())->exists(), 404);

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

        return view('catalog.index', compact('category', 'breadcrumbs'));
    }
}
