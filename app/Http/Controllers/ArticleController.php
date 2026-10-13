<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SeoRoute;
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
            ->latest('id')
            ->paginate((int) config('pagination.per_page.articles'));
        $seo = $this->seoService->forRoute(SeoRoute::Articles);

        return view('articles.index', compact('articles', 'seo'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->is_active, 404);

        $seo = $this->seoService->forModel(
            seoable: $article,
            fallbackTitle: $article->title,
            fallbackDescription: $article->excerpt ?? __('Полезная статья о натуральных продуктах GeoMiLife.'),
        );

        return view('articles.show', compact('article', 'seo'));
    }
}
