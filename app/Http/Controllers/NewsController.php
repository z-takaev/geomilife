<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\News;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use App\Support\Seo\Schemas\NewsArticleSchema;
use Illuminate\Contracts\View\View;

final class NewsController extends Controller
{
    public function index(): View
    {
        $newsItems = News::query()
            ->with('media')
            ->where('is_active', true)
            ->latest('published_at')
            ->paginate(config('pagination.per_page.news'));

        seo()->forRoute(
            routeName: 'news.index',
            fallbackTitle: __('Новости'),
        )->schema(BreadcrumbSchema::make([
            __('Главная') => route('home'),
            __('Новости') => route('news.index'),
        ]));

        return view('news.index', compact('newsItems'));
    }

    public function show(News $news): View
    {
        abort_unless($news->is_active, 404);

        seo()->forModel(
            seoable: $news,
            fallbackTitle: $news->title,
        )
            ->image($news->getFirstMediaUrl('image'))
            ->type('article')
            ->schema(NewsArticleSchema::make($news))
            ->schema(BreadcrumbSchema::make([
                __('Главная') => route('home'),
                __('Новости') => route('news.index'),
                $news->title => route('news.show', $news),
            ]));

        return view('news.show', compact('news'));
    }
}
