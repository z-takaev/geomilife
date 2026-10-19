<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\News;
use App\Models\Page;
use App\Models\Product;
use App\Models\Promo;
use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['location' => route('home'), 'lastModified' => null],
            ['location' => route('contacts.index'), 'lastModified' => null],
            ['location' => route('faqs.index'), 'lastModified' => null],
            ['location' => route('reviews.index'), 'lastModified' => null],
            ['location' => route('news.index'), 'lastModified' => null],
            ['location' => route('articles.index'), 'lastModified' => null],
            ['location' => route('promos.index'), 'lastModified' => null],
            ['location' => route('catalog.index'), 'lastModified' => null],
            ['location' => route('search.index'), 'lastModified' => null],
            ['location' => route('checkout.index'), 'lastModified' => null],
        ])->concat(
            Page::query()
                ->where('is_active', true)
                ->get(['slug', 'updated_at'])
                ->map(static fn (Page $page): array => [
                    'location' => route('pages.show', $page),
                    'lastModified' => $page->updated_at,
                ]),
        )->concat(
            News::query()
                ->where('is_active', true)
                ->get(['slug', 'updated_at'])
                ->map(static fn (News $news): array => [
                    'location' => route('news.show', $news),
                    'lastModified' => $news->updated_at,
                ]),
        )->concat(
            Article::query()
                ->where('is_active', true)
                ->get(['slug', 'updated_at'])
                ->map(static fn (Article $article): array => [
                    'location' => route('articles.show', $article),
                    'lastModified' => $article->updated_at,
                ]),
        )->concat(
            Product::query()
                ->where('is_active', true)
                ->get(['slug', 'updated_at'])
                ->map(static fn (Product $product): array => [
                    'location' => route('products.show', $product),
                    'lastModified' => $product->updated_at,
                ]),
        )->concat(
            Promo::query()
                ->where('is_active', true)
                ->get(['slug', 'updated_at'])
                ->map(static fn (Promo $promo): array => [
                    'location' => route('promos.show', $promo),
                    'lastModified' => $promo->updated_at,
                ]),
        );

        return response()
            ->view('sitemap.index', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
