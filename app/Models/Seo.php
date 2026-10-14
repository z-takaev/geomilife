<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;

final class Seo extends Model
{
    protected $table = 'seo_metadata';

    protected $fillable = [
        'route_name',
        'title',
        'description',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
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
