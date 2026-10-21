<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\News;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use App\Support\Seo\Schemas\NewsArticleSchema;
use Illuminate\Contracts\View\View;

final class NewsController extends Controller
{
    public function index(): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Новости'), route('news.index')),
        );

        seo()->forRoute(
            routeName: 'news.index',
            fallbackTitle: __('Новости'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $newsItems = News::query()
            ->with('media')
            ->where('is_active', true)
            ->latest('published_at')
            ->paginate(config('pagination.per_page.news'));

        return view('news.index', compact('newsItems', 'breadcrumbs'));
    }

    public function show(News $news): View
    {
        abort_unless($news->is_active, 404);

        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Новости'), route('news.index')),
            BreadcrumbItem::make($news->title, route('news.show', $news)),
        );

        seo()->forModel(
            seoable: $news,
            fallbackTitle: $news->title,
        )
            ->image($news->getFirstMediaUrl('image'))
            ->type('article')
            ->schema(NewsArticleSchema::make($news))
            ->schema(BreadcrumbSchema::make($breadcrumbs));

        return view('news.show', compact('news', 'breadcrumbs'));
    }
}
