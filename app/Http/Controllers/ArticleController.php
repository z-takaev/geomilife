<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class ArticleController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function index(): View
    {
        $articles = Article::query()
            ->with('media')
            ->where('is_active', true)
            ->latest('published_at')
            ->paginate(config('pagination.per_page.articles'));

        $seo = $this->seoService->forRoute(
            routeName: 'articles.index',
            fallbackTitle: __('Статьи'),
        );

        return view('articles.index', compact('articles', 'seo'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->is_active, 404);

        $seo = $this->seoService->forModel(
            seoable: $article,
            fallbackTitle: $article->title,
        );

        return view('articles.show', compact('article', 'seo'));
    }
}
