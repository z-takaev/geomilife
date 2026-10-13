<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Article;
use App\Models\News;
use App\Models\Page;
use App\Models\Promo;
use App\Observers\SeoableObserver;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Article::observe(SeoableObserver::class);
        News::observe(SeoableObserver::class);
        Page::observe(SeoableObserver::class);
        Promo::observe(SeoableObserver::class);
    }
}
