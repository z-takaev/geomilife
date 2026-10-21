<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Support\Seo\Schemas\ArticleSchema;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()
            ->with('media')
            ->where('is_active', true)
            ->latest('published_at')
            ->paginate(config('pagination.per_page.articles'));

        seo()->forRoute(
            routeName: 'articles.index',
            fallbackTitle: __('Статьи'),
        )->schema(BreadcrumbSchema::make([
            __('Главная') => route('home'),
            __('Статьи') => route('articles.index'),
        ]));

        return view('articles.index', compact('articles'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->is_active, 404);

        seo()->forModel(
            seoable: $article,
            fallbackTitle: $article->title,
        )
            ->image($article->getFirstMediaUrl('image'))
            ->type('article')
            ->schema(ArticleSchema::make($article))
            ->schema(BreadcrumbSchema::make([
                __('Главная') => route('home'),
                __('Статьи') => route('articles.index'),
                $article->title => route('articles.show', $article),
            ]));

        return view('articles.show', compact('article'));
    }
}
