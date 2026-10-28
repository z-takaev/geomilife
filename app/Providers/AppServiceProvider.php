<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Products\CatalogRepository;
use App\Services\Locations\LocationService;
use App\Support\Seo\SeoManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(SeoManager::class);
        $this->app->scoped(CatalogRepository::class);
        $this->app->scoped(LocationService::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
