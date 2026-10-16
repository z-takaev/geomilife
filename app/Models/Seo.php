<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

final class Seo extends Model
{
    private const string ROUTE_CACHE_KEY = 'seo_route_metadata';

    protected $table = 'seo_metadata';

    protected $fillable = [
        'route_name',
        'title',
        'description',
    ];

    protected static function booted(): void
    {
        self::saved(static function (self $seo): void {
            if ($seo->route_name !== null || $seo->getRawOriginal('route_name') !== null) {
                Cache::forget(self::ROUTE_CACHE_KEY);
            }
        });

        self::deleted(static function (self $seo): void {
            if ($seo->route_name !== null) {
                Cache::forget(self::ROUTE_CACHE_KEY);
            }
        });
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return Collection<string, self>
     */
    public static function cachedRoutes(): Collection
    {
        return Cache::remember(
            self::ROUTE_CACHE_KEY,
            now()->addHours(2),
            static fn (): Collection => self::query()
                ->whereNotNull('route_name')
                ->get()
                ->keyBy('route_name'),
        );
    }

    public function getPathAttribute(): ?string
    {
        $route = $this->resolveRoute();

        if ($route === null) {
            return null;
        }

        return '/'.ltrim($route->uri(), '/');
    }

    public function getUrlAttribute(): ?string
    {
        $route = $this->resolveRoute();

        if ($route === null) {
            return null;
        }

        return route($route->getName());
    }

    private function resolveRoute(): ?LaravelRoute
    {
        if (blank($this->route_name)) {
            return null;
        }

        return Route::getRoutes()->getByName($this->route_name);
    }
}
