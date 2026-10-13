<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SeoRoute;
use App\Models\News;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class NewsController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function index(): View
    {
        $newsItems = News::query()
            ->with('media')
            ->where('is_active', true)
            ->latest('published_at')
            ->latest('id')
            ->paginate((int) config('pagination.per_page.news'));
        $seo = $this->seoService->forRoute(SeoRoute::News);

        return view('news.index', compact('newsItems', 'seo'));
    }

    public function show(News $news): View
    {
        abort_unless($news->is_active, 404);

        $seo = $this->seoService->forModel(
            seoable: $news,
            fallbackTitle: $news->title,
            fallbackDescription: $news->excerpt ?? __('Новости производства натуральных продуктов GeoMiLife.'),
        );

        return view('news.show', compact('news', 'seo'));
    }
}
