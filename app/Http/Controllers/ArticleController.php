<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\ArticleSchema;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class ArticleController extends Controller
{
    public function index(): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Статьи'), route('articles.index')),
        );

        seo()->forRoute(
            routeName: 'articles.index',
            fallbackTitle: __('Статьи'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $articles = Article::query()
            ->with('media')
            ->published()
            ->latest('published_at')
            ->paginate(config('pagination.per_page.articles'));

        return view('articles.index', compact('articles', 'breadcrumbs'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->isAvailable(), 404);

        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Статьи'), route('articles.index')),
            BreadcrumbItem::make($article->title, route('articles.show', $article)),
        );

        seo()->forModel(
            seoable: $article,
            fallbackTitle: $article->title,
        )
            ->image($article->getFirstMediaUrl('image'))
            ->type('article')
            ->schema(ArticleSchema::make($article))
            ->schema(BreadcrumbSchema::make($breadcrumbs));

        return view('articles.show', compact('article', 'breadcrumbs'));
    }
}
